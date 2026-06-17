<?php

namespace Tests\Hermetic;

use PHPUnit\Framework\TestCase;
use Meabed\ParallelSoap\ParallelSoapClient;

/**
 * Configuration behaviour that does not require a running server.
 */
class ConfigTest extends TestCase
{
    public function testProxyOptionsAreMappedToCurl(): void
    {
        $wsdl = dirname(__DIR__) . '/Fixtures/calculator.wsdl';

        $client = new ParallelSoapClient($wsdl, [
            'proxy_host' => 'proxy.example.com',
            'proxy_port' => 8080,
            'proxy_login' => 'user',
            'proxy_password' => 'secret',
        ]);

        $options = $client->getCurlOptions();

        $this->assertSame('proxy.example.com', $options[CURLOPT_PROXY]);
        $this->assertSame(8080, $options[CURLOPT_PROXYPORT]);
        $this->assertSame('user:secret', $options[CURLOPT_PROXYUSERPWD]);
    }

    public function testRunResetsClientToSingleMode(): void
    {
        $wsdl = dirname(__DIR__) . '/Fixtures/calculator.wsdl';

        $client = new ParallelSoapClient($wsdl);
        $this->assertFalse($client->getMulti());

        $client->setMulti(true);
        $this->assertTrue($client->getMulti());
    }
}
