<?php

namespace Tests\Base;

use PHPUnit\Framework\TestCase;
use Soap\ParallelSoapClient;

class BaseExampleCase extends TestCase
{
    /** @var \Soap\ParallelSoapClient */
    public $parallelSoapClient;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        // parse response function
        $parseResultFn = function ($method, $res) {
            switch ($method) {
                case 'Login':
                    $ret = $res->SessionId;
                    break;
                case 'SayHello':
                    $ret = $res->Text;
                    break;
                case 'GetFullName':
                    $ret = $res->FullName;
                    break;
                default:
                    $ret = $res;
            }
            return $ret;
        };

        /** @var string $wsdl , This is the test server i have generated to test the class */
        $wsdl = "https://whispering-meadow-99755.herokuapp.com/wsdl.php";
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
    }
}
