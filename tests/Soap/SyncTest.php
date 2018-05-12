<?php

namespace Tests\Soap;

use Tests\Base\BaseCase;

class SyncTest extends BaseCase
{
    public function testLogin()
    {
        $rs = $this->asyncSoapClient->login(['demo', '123456']);
        $this->assertEquals('demo123456-loggedin', $rs);
    }

    public function testInvalidSession()
    {
        // invalid session exception
        try {
            $this->asyncSoapClient->sayHello('demo', '123456');
        } catch (\Exception $e) {
            $this->assertEquals(\SoapFault::class, get_class($e));
            $this->assertContains('Invalid session', $e->getMessage());
        }
    }

    public function testInvalidParam()
    {
        // invalid params exception
        try {
            $this->asyncSoapClient->sayHello('demo123456-loggedin', null);
        } catch (\Exception $e) {
            $this->assertEquals(\SoapFault::class, get_class($e));
            $this->assertContains('Invalid params', $e->getMessage());
        }
    }

    public function testSayHello()
    {
        $rs = $this->asyncSoapClient->sayHello('demo123456-loggedin', 'Someone Name');
        $this->assertContains('Hello Someone Name', $rs);

        $rs = $this->asyncSoapClient->sayHello('demo123456-loggedin', 'Her Name');
        $this->assertContains('Hello Her Name', $rs);
    }

}