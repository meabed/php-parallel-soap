<?php

namespace Tests\Hermetic;

use PHPUnit\Framework\TestCase;
use Meabed\ParallelSoap\ParallelSoapClient;
use Tests\Support\SoapTestServer;

/**
 * Base class for the hermetic tests that run against a local, in-process SOAP
 * server. No external network endpoint is involved, so these tests are fast and
 * fully deterministic in CI.
 */
abstract class HermeticTestCase extends TestCase
{
    protected static SoapTestServer $server;

    public static function setUpBeforeClass(): void
    {
        self::$server = SoapTestServer::start();
    }

    public static function tearDownAfterClass(): void
    {
        self::$server->stop();
    }

    protected function newClient(bool $multi, ?string $endpoint = null): ParallelSoapClient
    {
        $wsdl = dirname(__DIR__) . '/Fixtures/calculator.wsdl';

        $options = [
            'location' => $endpoint ?? self::$server->url,
            'trace' => true,
            'exceptions' => true,
            'soap_version' => SOAP_1_1,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'encoding' => 'UTF-8',
            // Unwrap the `<OperationResult>` wrapper element into a scalar value.
            'resFn' => static fn ($method, $res) => $res->{$method . 'Result'} ?? $res,
        ];

        $client = new ParallelSoapClient($wsdl, $options);
        $client->setMulti($multi);
        $client->setCurlOptions([
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        return $client;
    }
}
