<?php

namespace Tests\Base;

use PHPUnit\Framework\TestCase;
use Soap\ParallelSoapClient;

class BaseCrcindCase extends TestCase
{
    /** @var \Soap\ParallelSoapClient */
    public $parallelSoapClient;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        // parse response function
        $soapActionFn = function ($action, $headers) {
            $headers[] = 'SOAPAction: "' . $action . '"';
            // 'SOAPAction: "' . $soapAction . '"', pass the soap action in every request from the WSDL if required
            return $headers;
        };

        // parse response function
        $parseResultFn = function ($method, $res) {
            if (isset($res->{$method . 'Result'})) {
                return $res->{$method . 'Result'};
            }
            return $res;
        };

        $formatXmlFn = function ($request) {
            $dom = new \DOMDocument();
            $dom->preserveWhiteSpace = false;
            $dom->loadXML($request);
            $dom->formatOutput = true;

            return $dom->saveXml();
        };


        // @link http://www.crcind.com/csp/samples/SOAP.Demo.cls?WSDL
        /** @var string $wsdl , This is the test server i have generated to test the class */
        $wsdl = "http://www.crcind.com/csp/samples/SOAP.Demo.cls?WSDL";
        /** @var array $options , array of options for the soap client */
        $options = [
            'connection_timeout' => 40,
            'trace' => true,
            'exceptions' => true,
            'soap_version' => SOAP_1_1,
            'cache_wsdl' => WSDL_CACHE_BOTH,
            'encoding' => 'UTF-8',
            'resFn' => $parseResultFn,
            'soapActionFn' => $soapActionFn,
            'formatXmlFn' => $formatXmlFn,
        ];

        /** @var \Soap\ParallelSoapClient $client New Soap client instance */
        $this->parallelSoapClient = new ParallelSoapClient($wsdl, $options);
        $this->parallelSoapClient->setLogSoapRequest(1);
        $this->parallelSoapClient->setLogger(new StdoutLogger());
    }
}
