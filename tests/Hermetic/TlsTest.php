<?php

namespace Tests\Hermetic;

use PHPUnit\Framework\TestCase;
use Soap\ParallelSoapClient;
use Tests\Support\TlsSoapTestServer;

/**
 * Exercises the curl/TLS code path of the client against a local HTTPS SOAP
 * server secured with a freshly generated self-signed certificate.
 */
class TlsTest extends TestCase
{
    private static TlsSoapTestServer $server;

    public static function setUpBeforeClass(): void
    {
        if (!TlsSoapTestServer::isSupported()) {
            self::markTestSkipped('The openssl extension is required for the TLS test.');
        }
        self::$server = TlsSoapTestServer::start();
    }

    public static function tearDownAfterClass(): void
    {
        if (isset(self::$server)) {
            self::$server->stop();
        }
    }

    private function newTlsClient(bool $multi): ParallelSoapClient
    {
        $wsdl = dirname(__DIR__) . '/Fixtures/calculator.wsdl';

        $client = new ParallelSoapClient($wsdl, [
            'location' => self::$server->url,
            'trace' => true,
            'exceptions' => true,
            'soap_version' => SOAP_1_1,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'resFn' => static fn ($method, $res) => $res->{$method . 'Result'} ?? $res,
        ]);
        $client->setMulti($multi);
        $client->setCurlOptions([
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);

        return $client;
    }

    public function testSingleCallOverTls(): void
    {
        $client = $this->newTlsClient(false);
        $this->assertEquals('7', $client->Add(['intA' => 4, 'intB' => 3]));
    }

    public function testParallelCallsOverTls(): void
    {
        $client = $this->newTlsClient(true);

        $req1 = $client->Add(['intA' => 4, 'intB' => 3]);
        $req2 = $client->Multiply(['intA' => 6, 'intB' => 7]);

        $responses = $client->run();

        $this->assertEquals('7', $responses[$req1]);
        $this->assertEquals('42', $responses[$req2]);
    }

    public function testTlsConnectionMetadataIsCaptured(): void
    {
        $client = $this->newTlsClient(true);

        $req1 = $client->Add(['intA' => 1, 'intB' => 1]);
        $client->run();

        $this->assertArrayHasKey($req1, $client->curlInfo);
        $this->assertSame('https', parse_url($client->curlInfo[$req1]->url, PHP_URL_SCHEME));
    }
}
