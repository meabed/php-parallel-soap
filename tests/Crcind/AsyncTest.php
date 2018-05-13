<?php

namespace Tests\Crcind;

use Tests\Base\BaseCrcindCase;

class AsyncTest extends BaseCrcindCase
{
    public function testAddInteger()
    {
        $this->asyncSoapClient->setAsync(true);

        $req1 = $this->asyncSoapClient->AddInteger(['Arg1' => 4, 'Arg2' => 3]);
        $req2 = $this->asyncSoapClient->AddInteger(['Arg1' => 1, 'Arg2' => 2]);
        $req3 = $this->asyncSoapClient->AddInteger(['Arg1' => 3, 'Arg2' => 5]);

        $rs = $this->asyncSoapClient->run();

        $this->assertEquals('7', $rs[$req1]);
        $this->assertEquals('3', $rs[$req2]);
        $this->assertEquals('8', $rs[$req3]);
    }
//    public function testAsync1()
//    {
//        $this->asyncSoapClient->setAsync(true);
//
//        $req1 = $this->asyncSoapClient->Login(['demo', '123456']); // valid
//        $req2 = $this->asyncSoapClient->SayHello('demo1', 'NAME0123456'); // invalid session
//        $req3 = $this->asyncSoapClient->SayHello('demo123456-loggedin'); // invalid param
//
//        $req4 = $this->asyncSoapClient->GetFullName('demo123456-loggedin', 'Fname1', 'Lname1'); // valid
//        $req5 = $this->asyncSoapClient->GetFullName('demo123456-loggedin', 'Fname2', 'Lname2'); // valid
//        $req6 = $this->asyncSoapClient->GetFullName('loggedin', 'Fname3', 'Lname3'); // invalid
//        $req7 = $this->asyncSoapClient->GetFullName('demo123456-loggedin', 'Fname4', 'Lname4'); // valid
//        $req8 = $this->asyncSoapClient->SayHello('demo123456-loggedin', 'NAME0123456'); // valid
//
//        $rs = $this->asyncSoapClient->run();
//
//        $this->assertEquals('demo123456-loggedin', $rs[$req1]);
//
//        $this->assertEquals('Invalid session', $rs[$req2]->getMessage());
//        $this->assertEquals(\SoapFault::class, get_class($rs[$req2]));
//
//        $this->assertEquals('Invalid params', $rs[$req3]->getMessage());
//        $this->assertEquals(\SoapFault::class, get_class($rs[$req3]));
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