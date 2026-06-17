<?php

namespace Tests\Base;

use PHPUnit\Framework\TestCase;
use Meabed\ParallelSoap\ParallelSoapClient;
use Tests\Support\RemoteHost;

/**
 * Integration tests against the public dneonline.com calculator SOAP service.
 *
 * These tests hit a third-party host, so the concrete subclasses are tagged with
 * the #[Group('external')] attribute and they skip automatically when the host is
 * unreachable.
 */
abstract class BaseDneCase extends TestCase
{
    protected const HOST = 'www.dneonline.com';
    protected const WSDL = 'http://www.dneonline.com/calculator.asmx?WSDL';

    protected ParallelSoapClient $parallelSoapClient;

    protected function setUp(): void
    {
        if (!RemoteHost::isReachable(self::HOST)) {
            $this->markTestSkipped(self::HOST . ' is not reachable; skipping external test.');
        }

        // Bound the WSDL fetch so a misbehaving host fails fast instead of hanging.
        ini_set('default_socket_timeout', '10');

        $parseResultFn = static function ($method, $res) {
            if (isset($res->{$method . 'Result'})) {
                return $res->{$method . 'Result'};
            }
            return $res;
        };

        $options = [
            'connection_timeout' => 15,
            'trace' => true,
            'exceptions' => true,
            'soap_version' => SOAP_1_1,
            'cache_wsdl' => WSDL_CACHE_BOTH,
            'encoding' => 'UTF-8',
            'resFn' => $parseResultFn,
        ];

        $this->parallelSoapClient = new ParallelSoapClient(self::WSDL, $options);
        $this->parallelSoapClient->setLogSoapRequest(true);
        $this->parallelSoapClient->setLogger(new StdoutLogger());
    }
}
