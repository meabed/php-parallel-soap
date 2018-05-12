<?php

namespace Tests\Base;

use PHPUnit\Framework\TestCase;
use Soap\SoapClientAsync;

class BaseCase extends TestCase
{
    /** @var \Soap\SoapClientAsync */
    public $asyncSoapClient;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        /** @var string $wsdl , This is the test server i have generated to test the class */
        $wsdl = "https://whispering-meadow-99755.herokuapp.com/wsdl.php";
        /** @var array $options , array of options for the soap client */
        $options = [
            'connection_timeout' => 40,
            'trace' => true,
        ];

        /** @var \Soap\SoapClientAsync $client New Soap client instance */
        $this->asyncSoapClient = new SoapClientAsync($wsdl, $options);
    }
}
