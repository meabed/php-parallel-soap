<?php

namespace Tests\Dne;

use Tests\Base\BaseDneCase;

class AsyncTest extends BaseDneCase
{
    public function testAddInteger()
    {
        $this->asyncSoapClient->setAsync(true);

        $req1 = $this->asyncSoapClient->Add(['intA' => 4, 'intB' => 3]);
        $req2 = $this->asyncSoapClient->Add(['intA' => 1, 'intB' => 2]);
        $req3 = $this->asyncSoapClient->Add(['intA' => 3, 'intB' => 5]);

        $rs = $this->asyncSoapClient->run();

        $this->assertEquals('7', $rs[$req1]);
        $this->assertEquals('3', $rs[$req2]);
        $this->assertEquals('8', $rs[$req3]);

        $this->asyncSoapClient->setAsync(false);
        $rs = $this->asyncSoapClient->Add(['intA' => 3, 'intB' => 10]);
        $this->assertEquals('13', $rs);

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