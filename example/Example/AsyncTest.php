<?php

namespace Tests\Example;

use Tests\Base\BaseExampleCase;

class AsyncTest extends BaseExampleCase
{
    public function testAsync1()
    {
        $this->asyncSoapClient->setAsync(true);

        $req1 = $this->asyncSoapClient->Login(['demo', '123456']); // valid
        $req2 = $this->asyncSoapClient->SayHello('demo1', 'NAME0123456'); // invalid session
        $req3 = $this->asyncSoapClient->SayHello('demo123456-loggedin'); // invalid param

        $req4 = $this->asyncSoapClient->GetFullName('demo123456-loggedin', 'Fname1', 'Lname1'); // valid
        $req5 = $this->asyncSoapClient->GetFullName('demo123456-loggedin', 'Fname2', 'Lname2'); // valid
        $req6 = $this->asyncSoapClient->GetFullName('loggedin', 'Fname3', 'Lname3'); // invalid
        $req7 = $this->asyncSoapClient->GetFullName('demo123456-loggedin', 'Fname4', 'Lname4'); // valid
        $req8 = $this->asyncSoapClient->SayHello('demo123456-loggedin', 'NAME0123456'); // valid

        $rs = $this->asyncSoapClient->run();

        $this->assertEquals('demo123456-loggedin', $rs[$req1]);

        $this->assertEquals('Invalid session', $rs[$req2]->getMessage());
        $this->assertEquals(\SoapFault::class, get_class($rs[$req2]));

        $this->assertEquals('Invalid params', $rs[$req3]->getMessage());
        $this->assertEquals(\SoapFault::class, get_class($rs[$req3]));
    }

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