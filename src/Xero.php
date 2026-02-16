<?php

declare(strict_types=1);

namespace Almani\Xero;

use Almani\Xero\Actions\StoreTokenAction;
use Almani\Xero\Actions\tokenExpiredAction;
use Almani\Xero\Models\XeroToken;
use Almani\Xero\Resources\Contacts;
use Almani\Xero\Resources\CreditNotes;
use Almani\Xero\Resources\Invoices;
use Almani\Xero\Resources\Webhooks;
use Almani\Xero\Traits\XeroHelpersTrait;
use Exception;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * @method static array get (string $endpoint, array $params = [])
 * @method static array put (string $endpoint, array $params = [])
 * @method static array post (string $endpoint, array $params = [])
 * @method static array patch (string $endpoint, array $params = [])
 * @method static array delete (string $endpoint, array $params = [])
 */
class Xero
{
    use XeroHelpersTrait;

    protected static string $baseUrl = 'https://api.xero.com/api.xro/2.0/';

    protected static string $authorizeUrl = 'https://login.xero.com/identity/connect/authorize';

    protected static string $connectionUrl = 'https://api.xero.com/connections';

    protected static string $tokenUrl = 'https://identity.xero.com/connect/token';

    protected static string $revokeUrl = 'https://identity.xero.com/connect/revocation';

    protected string $tenant_id = '';
    protected ?int $companyId = null;

    public function __construct(?string $tenantId = null, ?int $companyId = null)
    {
        if ($tenantId) {
            $this->tenant_id = $tenantId;
        }
        if ($companyId) {
            $this->companyId = $companyId;
        }
    }

    /**
     * __call catches all requests when no found method is requested
     *
     * @param  string  $function  - the verb to execute
     * @param  array  $args  - array of arguments
     * @return array
     *
     * @throws Exception
     */
    public function __call(string $function, array $args)
    {
        $options = ['get', 'post', 'patch', 'put', 'delete'];
        $path = $args[0] ?? '';
        $data = $args[1] ?? [];
        $raw = $args[2] ?? false;
        $accept = $args[3] ?? 'application/json';
        $headers = $args[4] ?? []; // Add a new line for custom headers

        if (in_array($function, $options)) {
            return $this->guzzle($function, $path, $data, $raw, $accept, $headers);
        }
        // request verb is not in the $options array
        throw new RuntimeException($function.' is not a valid HTTP Verb');
    }


    public function setTenantId(string $tenant_id): void
    {
        $this->tenant_id = $tenant_id;
    }
    public function setCompanyId(int $companyId): void
    {
        $this->companyId = $companyId;
    }

    public function contacts(): Contacts
    {
        return new Contacts;
    }

    public function creditnotes(): CreditNotes
    {
        return new CreditNotes;
    }

    public function invoices(): Invoices
    {
        return new Invoices;
    }

    public function webhooks(): Webhooks
    {
        return new Webhooks;
    }

    public function isTokenValid(): bool
    {
        $token = $this->getTokenData();

        if ($token === null) {
            return false;
        }

        $now = now()->addMinutes(5);

        if ($token->expires < $now) {
            return false;
        }

        return true;
    }

    public function isConnected(): bool
    {
        return ! ($this->getTokenData() === null);
    }

    public function disconnect(): void
    {
        try {
            $token = $this->getTokenData();

            Http::withHeaders([
                'authorization' => 'Basic '.base64_encode(config('xero.clientId').':'.config('xero.clientSecret')),
            ])
                ->asForm()
                ->post(self::$revokeUrl, [
                    'token' => $token->refresh_token,
                ])->throw();

            $token->delete();
        } catch (Exception $e) {
            throw new RuntimeException('error getting tenant: '.$e->getMessage());
        }
    }
    /**
     * Make a connection or return a token where it's valid
     *
     * @throws \Exception
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector | Illuminate\Contracts\Foundation\Application;
     */
    public function connect()
    {
        // when no code param redirect to Microsoft
        if (request()->has('code')) {
            // With the authorization code, we can retrieve access tokens and other data.
            try {
                $params = [
                    'grant_type' => 'authorization_code',
                    'code' => request('code'),
                    'redirect_uri' => config('xero.redirectUri'),
                ];

                $result = $this->sendPost(self::$tokenUrl, $params);
                $company_id = null;
                if(request()->has('state')) {
                    $state = json_decode(base64_decode(request('state')), true);
                    $company_id = $state['company_id'];
                }
                try {
                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer '.$result['access_token'],
                    ])
                        ->acceptJson()
                        ->get(self::$connectionUrl)
                        ->throw()
                        ->json();
                    foreach ($response as $tenant) {
                        $tenantData = [
                            'auth_event_id' => $tenant['authEventId'],
                            'tenant_id' => $tenant['tenantId'],
                            'tenant_type' => $tenant['tenantType'],
                            'tenant_name' => $tenant['tenantName'],
                            'created_date_utc' => $tenant['createdDateUtc'],
                            'updated_date_utc' => $tenant['updatedDateUtc'],
                            'company_id' => $company_id,
                        ];

                        app(StoreTokenAction::class)($result, $tenantData, $tenant['tenantId'],$company_id);
                    }
                } catch (Exception $e) {
                    throw new Exception('Error getting tenant: '.$e->getMessage());
                }

                return redirect(config('xero.landingUri'));
            } catch (Exception $e) {
                throw new Exception($e->getMessage());
            }
        }

        $url = self::$authorizeUrl.'?'.http_build_query([
                'response_type' => 'code',
                'client_id' => config('xero.clientId'),
                'redirect_uri' => config('xero.redirectUri'),
                'scope' => config('xero.scopes'),
            ]);
        return redirect()->away(trim($url));
    }
    /**
     * Get the Xero token for the current tenant or the first available.
     *
     * @return \App\Models\XeroToken|null
     */
    public function getTokenData()
    {
        if ($this->tenant_id) {
            $token = XeroToken::where('tenant_id', '=', $this->tenant_id)->where('company_id','=',$this->companyId)->orderBy('created_at', 'desc')->first();
        } else {
            $token = XeroToken::orderBy('created_at', 'desc')->first();
        }
        if ($token && config('xero.encrypt')) {
            try {
                $access_token = Crypt::decryptString($token->access_token);
            } catch (DecryptException $e) {
                $access_token = $token->access_token;
            }

            // Split them as a refresh token may not exist...
            try {
                $refresh_token = Crypt::decryptString($token->refresh_token);
            } catch (DecryptException $e) {
                $refresh_token = $token->refresh_token;
            }

            $token->access_token = $access_token;
            $token->refresh_token = $refresh_token;
        }

        return $token;
    }

    /**
     * @throws Exception
     */
    public function getAccessToken(bool $redirectWhenNotConnected = true): string
    {
        /* @var XeroToken $token */
        $token = $this->getTokenData();

        $this->redirectIfNoToken($token, $redirectWhenNotConnected);

        $now = now()->addMinutes(5);

        if ($token->expires < $now) {
            return $this->renewExpiringToken($token);
        }

        return $token->access_token;
    }

    /**
     * @throws Exception
     */
    public function renewExpiringToken(XeroToken $token): string
    {
        $params = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $token->refresh_token,
            'redirect_uri' => config('xero.redirectUri'),
        ];

        $result = $this->sendPost(self::$tokenUrl, $params);

        app(tokenExpiredAction::class)($result, $token);
        app(StoreTokenAction::class)($result, ['tenant_id' => $token->tenant_id], $this->tenant_id,$this->companyId);

        return $result['access_token'];
    }

    public function getTenantId(): string
    {
        $token = $this->getTokenData();

        $this->redirectIfNoToken($token);

        return $token->tenant_id;
    }

    public function getTenantName(): ?string
    {
        // use id if passed otherwise use logged-in user
        $token = $this->getTokenData();

        $this->redirectIfNoToken($token);

        // Token is still valid, just return it
        return $token->tenant_name;
    }


    /**
     * @throws Exception
     */
    protected static function sendPost(string $url, array $params): array
    {
        try {
            $response = Http::withHeaders([
                'authorization' => 'Basic '.base64_encode(config('xero.clientId').':'.config('xero.clientSecret')),
            ])
                ->asForm()
                ->acceptJson()
                ->post($url, $params);

            return $response->json();

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Redirect if no Xero token exists.
     *
     * @param XeroToken $token
     * @param bool $redirectWhenNotConnected
     * @return \Illuminate\Http\RedirectResponse|bool
     */
    protected function redirectIfNoToken(XeroToken $token, bool $redirectWhenNotConnected = true)
    {
        // Check if tokens exist otherwise run the oauth request
        if (! $this->isConnected() && $redirectWhenNotConnected === true) {
            return redirect()->away(config('xero.redirectUri'));
        }

        return false;
    }


    /**
     * run Guzzle to process the requested url
     *
     * @throws Exception
     */
    protected function guzzle(string $type, string $request, array $data = [], bool $raw = false, string $accept = 'application/json', array $headers = []): array
    {
        if ($data === []) {
            $data = null;
        }

        try {
            $response = Http::withToken($this->getAccessToken())
                ->withHeaders(array_merge(['Xero-tenant-id' => $this->getTenantId()], $headers))
                ->accept($accept)
                ->$type(self::$baseUrl.$request, $data)
                ->throw();

            return [
                'body' => $raw ? $response->body() : $response->json(),
                'headers' => $response->getHeaders(),
            ];
        } catch (RequestException $e) {
           /* $response = json_decode($e->response->body());
            $message = isset($response->Detail)
                ? $response->Detail
                : sprintf(
                    "Type: %s Message: %s Error Number: %s",
                    isset($response->Type) ? $response->Type : null,
                    isset($response->Message) ? $response->Message : null,
                    isset($response->ErrorNumber) ? $response->ErrorNumber : null
                );
            throw new \Exception($message);*/

            $statusCode = $e->response->status() ?? null;
            $responseBody = $e->response ? $e->response->body() : null;
            $decoded = $responseBody ? json_decode($responseBody) : null;

            // Handle 404 separately
            if ($statusCode === 404) {
                \Log::warning('Xero API 404 Not Found', [
                    'request_url' => self::$baseUrl . $request,
                    'request_data' => $data,
                    'response_body' => $responseBody,
                    'decoded_response' => $decoded,
                ]);

                // Return null or empty array depending on your logic
                return [];
            }

            // For other errors, build safe message
            $message = $decoded && isset($decoded->Detail)
                ? $decoded->Detail
                : sprintf(
                    "HTTP %s Type: %s Message: %s Error Number: %s",
                    $statusCode ?? 'N/A',
                    isset($decoded->Type) ? $decoded->Type : 'N/A',
                    isset($decoded->Message) ? $decoded->Message : $e->getMessage(),
                    isset($decoded->ErrorNumber) ? $decoded->ErrorNumber : 'N/A'
                );

            \Log::error('Xero API RequestException', [
                'request_url' => self::$baseUrl . $request,
                'request_data' => $data,
                'response_body' => $responseBody,
                'decoded_response' => $decoded,
                'message' => $message
            ]);

            throw new \Exception($message);
           // throw new Exception($response->Detail ?? "Type: $response->Type ?? null Message: $response->Message ?? null Error Number: $response->ErrorNumber ?? null");
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }}
