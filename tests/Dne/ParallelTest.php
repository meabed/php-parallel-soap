<?php

namespace Tests\Dne;

use Tests\Base\BaseDneCase;

class ParallelTest extends BaseDneCase
{
    public function __construct($name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->parallelSoapClient->setMulti(true);
    }

    public function testAddInteger()
    {
        $req1 = $this->parallelSoapClient->Add(['intA' => 4, 'intB' => 3]);
        $req2 = $this->parallelSoapClient->Add(['intA' => 1, 'intB' => 2]);
        $req3 = $this->parallelSoapClient->Add(['intA' => 3, 'intB' => 5]);

        $rs = $this->parallelSoapClient->run();

        $this->assertEquals('7', $rs[$req1]);
        $this->assertEquals('3', $rs[$req2]);
        $this->assertEquals('8', $rs[$req3]);

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