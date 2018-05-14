<?php

namespace Tests\Base;

use PHPUnit\Framework\TestCase;
use Soap\ParallelSoapClient;

class BaseDneCase extends TestCase
{
    /** @var \Soap\ParallelSoapClient */
    public $parallelSoapClient;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        // parse response function
        $parseResultFn = function ($method, $res) {
            if (isset($res->{$method . 'Result'})) {
                return $res->{$method . 'Result'};
            }
            return $res;
        };

        /** @var string $wsdl , This is the test server i have generated to test the class */
        $wsdl = "http://www.dneonline.com/calculator.asmx?WSDL";
        /** @var array $options , array of options for the soap client */
        $options = [
            'connection_timeout' => 40,
            'trace' => true,
            'exceptions' => true,
            'soap_version' => SOAP_1_1,
            'cache_wsdl' => WSDL_CACHE_BOTH,
            'encoding' => 'UTF-8',
            'resFn' => $parseResultFn,
        ];

        /** @var \Soap\ParallelSoapClient $client New Soap client instance */
        $this->parallelSoapClient = new ParallelSoapClient($wsdl, $options);
        $this->parallelSoapClient->setLogSoapRequest(1);
        $this->parallelSoapClient->setLogger(new StdoutLogger());
    }
}
