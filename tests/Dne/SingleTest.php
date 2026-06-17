<?php

namespace Tests\Dne;

use PHPUnit\Framework\Attributes\Group;
use Tests\Base\BaseDneCase;

#[Group('external')]
class SingleTest extends BaseDneCase
{

    protected function setUp(): void
    {
        parent::setUp();
        $this->parallelSoapClient->setMulti(false);
    }

    public function testAddInteger(): void
    {
        $data = [
            'intA' => 4, 'intB' => 3,
        ];
        $rs = $this->parallelSoapClient->Add($data);
        $this->assertEquals('7', $rs);
    }

    public function testInvalidMethod(): void
    {
        // is not a valid method
        try {
            $this->parallelSoapClient->AddUnkown('demo', '123456');
        } catch (\Exception $e) {
            $this->assertEquals(\SoapFault::class, get_class($e));
            $this->assertStringContainsString('Function ("AddUnkown") is not a valid method for this service', $e->getMessage());
        }
    }

    public function testInvalidParam(): void
    {
        // invalid params exception
        try {
            $this->parallelSoapClient->Add(['a' => 1]);
        } catch (\Exception $e) {
            $this->assertEquals(\SoapFault::class, get_class($e));
            $this->assertStringContainsString('object has no \'intA\' property', $e->getMessage());
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
