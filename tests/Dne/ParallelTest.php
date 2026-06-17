<?php

namespace Tests\Dne;

use PHPUnit\Framework\Attributes\Group;
use Tests\Base\BaseDneCase;

#[Group('external')]
class ParallelTest extends BaseDneCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->parallelSoapClient->setMulti(true);
    }

    public function testAddInteger(): void
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
    // test pretty xml
    // test log shipping
    // test custom headers
    // add more example with log shipping / result parsing / etc...
}
