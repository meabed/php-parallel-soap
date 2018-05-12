<?php

namespace Tests\Base;

use PHPUnit\Framework\TestCase;

class BaseCase extends TestCase
{
    /** @var \SoapClientAsync  */
    public $asyncSoapClient;

    public function __construct(?string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        /** @var string $wsdl , This is the test server i have generated to test the class */
        $wsdl = "https://whispering-meadow-99755.herokuapp.com/wsdl.php";
        /** @var array $options , array of options for the soap client */
        $options = [
            'connection_timeout' => 40,
            'trace' => true,
        ];

        /** @var \SoapClientAsync $client New Soap client instance */
        $this->asyncSoapClient = new \SoapClientAsync($wsdl, $options);
    }
}
