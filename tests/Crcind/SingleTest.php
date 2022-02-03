<?php

namespace Tests\Crcind;

use Tests\Base\BaseCrcindCase;

class SingleTest extends BaseCrcindCase
{
    public function __construct($name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->parallelSoapClient->setMulti(false);
    }

    public function testAddInteger(): void
    {

        $data = [
            'Arg1' => 4, 'Arg2' => 3,
        ];
        $rs = $this->parallelSoapClient->AddInteger($data);
        $this->assertEquals('7', $rs);
    }

    public function testInvalidMethod(): void
    {
        // is not a valid method
        try {
            $this->parallelSoapClient->AddIntegerUnknown('demo', '123456');
        } catch (\Exception $e) {
            $this->assertEquals(\SoapFault::class, get_class($e));
            $this->assertStringContainsString('Function ("AddIntegerUnknown") is not a valid method for this service', $e->getMessage());
        }
    }

    public function testDivideInteger(): void
    {
        $data = [
            'Arg1' => 4, 'Arg2' => 2,
        ];
        $rs = $this->parallelSoapClient->DivideInteger($data);
        $this->assertEquals('2', $rs);
    }

    public function testDivideIntegerException(): void
    {
        $data = [
            'Arg1' => 4, 'Arg2' => 0,
        ];
        try {
            $rs = $this->parallelSoapClient->DivideInteger($data);
        } catch (\Exception $e) {
            $this->assertEquals(\SoapFault::class, get_class($e));
            $this->assertStringContainsString('DTD are not supported by SOAP', $e->getMessage());
        }
    }

    public function testGotNoXml(): void
    {
        try {
            $this->parallelSoapClient->LookupCity(['Mo', 'Meabed ']);
        } catch (\Exception $e) {
            // this is only available on trace=1 option in soapclient
            $soapRequest = $this->parallelSoapClient->__getLastRequest();
            $exceptionMessage = $this->parallelSoapClient->__getLastResponse();

            $this->assertEquals(\SoapFault::class, get_class($e));
            $this->assertStringContainsString('looks like we got no XML document', $e->getMessage());
            $this->assertStringContainsString('Bad Request', $exceptionMessage);
        }
    }

    // todo
    // test headers soap action
    // test curl info / debug data
    // test parser function
    // test pretty xml
    // test log shipping
    // test custom headers
    // add more example with log shipping / result parsing / etc...
}
