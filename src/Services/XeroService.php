<?php

namespace Almani\Xero\Services;

use League\OAuth2\Client\Provider\GenericProvider;
use XeroAPI\XeroPHP\Api\AccountingApi;
use XeroAPI\XeroPHP\Configuration;
use GuzzleHttp\Client;

class XeroService
{
    protected GenericProvider $provider;
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;

        // Safe defaults if config is missing
        $this->provider = new GenericProvider([
            'clientId'                => $config['client_id'] ?? '',
            'clientSecret'            => $config['client_secret'] ?? '',
            'redirectUri'             => $config['redirect_uri'] ?? '',
            'urlAuthorize'            => 'https://login.xero.com/identity/connect/authorize',
            'urlAccessToken'          => 'https://identity.xero.com/connect/token',
            'urlResourceOwnerDetails' => 'https://identity.xero.com/resources',
            'scopes'                  => isset($config['scopes']) ? explode(' ', $config['scopes']) : [],
        ]);
    }

    /**
     * Step 1: Get the URL to redirect user to Xero login
     */
    public function connect(): string
    {
        return $this->provider->getAuthorizationUrl();
    }

    /**
     * Step 2: Exchange auth code for access token
     */
    public function getAccessToken(string $code)
    {
        return $this->provider->getAccessToken('authorization_code', [
            'code' => $code,
        ]);
    }

    /**
     * Example: call Xero Accounting API safely
     */
    public function getContacts(string $accessToken, string $tenantId)
    {
        $config = Configuration::getDefaultConfiguration()->setAccessToken($accessToken);
        $apiInstance = new AccountingApi(new Client(), $config);

        return $apiInstance->getContacts($tenantId);
    }

    /**
     * Add more Xero API calls here (Invoices, Payments, etc.)
     */
}
