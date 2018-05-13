<?php

namespace Tests\Crcind;

use Tests\Base\BaseCrcindCase;

class SyncTest extends BaseCrcindCase
{
    public function testAddInteger()
    {
        $data = [
            'Arg1' => 4, 'Arg2' => 3,
        ];
        $rs = $this->asyncSoapClient->AddInteger($data);
        $this->assertEquals('7', $rs);
    }

    public function testInvalidMethod()
    {
        // is not a valid method
        try {
            $this->asyncSoapClient->AddIntegerUnknown('demo', '123456');
        } catch (\Exception $e) {
            $this->assertEquals(\SoapFault::class, get_class($e));
            $this->assertContains('Function ("AddIntegerUnknown") is not a valid method for this service', $e->getMessage());
        }
    }

//
//    public function testSayHello()
//    {
//        $rs = $this->asyncSoapClient->SayHello('demo123456-loggedin', 'Someone Name');
//        $this->assertContains('Hello Someone Name', $rs);
//
//        $rs = $this->asyncSoapClient->SayHello('demo123456-loggedin', 'Her Name');
//        $this->assertContains('Hello Her Name', $rs);
//    }
//
//    public function testGetFullName()
//    {
//        $rs = $this->asyncSoapClient->GetFullName('demo123456-loggedin', 'Fname', 'Lname');
//        $this->assertContains('Fname Lname', $rs);
//    }
//
//    public function testEchoText()
//    {
//        $rs = $this->asyncSoapClient->EchoText('demo123456-loggedin', 'TEST TEXT');
//        $this->assertContains('TEST TEXT', $rs);
//    }

    // todo
    // test headers soap action
    // test curl info / debug data
    // test parser function
    // test logger
    // test pretty xml
    // test log shipping
    // test custom headers
    // add more example with log shipping / result parsing / etc...
}