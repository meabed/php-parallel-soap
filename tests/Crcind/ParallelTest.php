<?php

namespace Tests\Crcind;

use Tests\Base\BaseCrcindCase;

class ParallelTest extends BaseCrcindCase
{
    public function __construct($name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->parallelSoapClient->setMulti(true);
        $this->parallelSoapClient->setCurlOptions(
            [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
            ]
        );

    }

    public function testAddInteger()
    {

        $req1 = $this->parallelSoapClient->AddInteger(['Arg1' => 4, 'Arg2' => 3]);
        $req2 = $this->parallelSoapClient->AddInteger(['Arg1' => 1, 'Arg2' => 2]);
        $req3 = $this->parallelSoapClient->AddInteger(['Arg1' => 3, 'Arg2' => 5]);

        $rs = $this->parallelSoapClient->run();

        $this->assertEquals('7', $rs[$req1]);
        $this->assertEquals('3', $rs[$req2]);
        $this->assertEquals('8', $rs[$req3]);
    }

    public function testAddIntegerDuplicate()
    {

        $req3 = $this->parallelSoapClient->AddInteger(['Arg1' => 3, 'Arg2' => 5]);

        $req4 = $this->parallelSoapClient->AddInteger(['Arg1' => 3, 'Arg2' => 5]); // duplicate as req3
        $this->assertEquals($req4, $req3);

        $rs = $this->parallelSoapClient->run();

        $this->assertEquals('8', $rs[$req3]);
        $this->assertEquals('8', $rs[$req4]);

    }

    public function testInvalidMethod()
    {

        // exception return
        $req3 = $this->parallelSoapClient->AddIntegerUnknown(['Arg1' => 3, 'Arg2' => 5]);

        $this->assertContains('Function ("AddIntegerUnknown") is not a valid method for this service', $req3);
    }

    public function testSwitchMultiClient()
    {

        $req1 = $this->parallelSoapClient->AddInteger(['Arg1' => 4, 'Arg2' => 3]);
        $req2 = $this->parallelSoapClient->AddInteger(['Arg1' => 1, 'Arg2' => 2]);
        $req3 = $this->parallelSoapClient->AddInteger(['Arg1' => 3, 'Arg2' => 5]);

        $rs = $this->parallelSoapClient->run();

        $this->assertEquals('7', $rs[$req1]);
        $this->assertEquals('3', $rs[$req2]);
        $this->assertEquals('8', $rs[$req3]);

        $this->parallelSoapClient->setMulti(false);
        $rs = $this->parallelSoapClient->AddInteger(['Arg1' => 3, 'Arg2' => 10]);
        $this->assertEquals('13', $rs);
        $this->parallelSoapClient->setMulti(true);

    }

    public function testGotNoXml()
    {
        $req1 = $this->parallelSoapClient->AddInteger(['Arg1' => 4, 'Arg2' => 3]);
        $req2 = $this->parallelSoapClient->AddInteger(['Arg1' => 1, 'Arg2' => 2]);
        $req3 = $this->parallelSoapClient->AddInteger(['Arg1' => 3, 'Arg2' => 5]);
        $req4 = $this->parallelSoapClient->LookupCity(['Mo', 'Meabed ']);

        $req5 = $this->parallelSoapClient->AddInteger(['Arg1' => 3, 'Arg2' => 5]);
        $req6 = $this->parallelSoapClient->AddInteger(['Arg1' => 3, 'Arg2' => 5]);
        $req7 = $this->parallelSoapClient->LookupCity(['Mo', 'Meabed ']);
        $req8 = $this->parallelSoapClient->AddInteger(['Arg1' => 3, 'Arg2' => 5]);

        $rs = $this->parallelSoapClient->run();

        $this->assertEquals('7', $rs[$req1]);
        $this->assertEquals('3', $rs[$req2]);
        $this->assertEquals('8', $rs[$req3]);

        // this is only available on trace=1 option in soapclient

        $this->assertEquals(\SoapFault::class, get_class($rs[$req4]));
        $this->assertContains('looks like we got no XML document', $rs[$req4]->getMessage());
        //$this->assertContains('Bad Request', $exceptionMessage);
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