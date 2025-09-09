<?php
namespace Almani\Xero\Tests;
use Orchestra\Testbench\TestCase;
use Almani\Xero\Services\XeroService;
class XeroServiceTest extends TestCase
{
    protected function getPackageProviders($app){ return ['Almani\\Xero\\XeroServiceProvider']; }
    public function testConfigLoaded(){ $service = new XeroService(config('xero')); $this->assertNotNull($service); }
}