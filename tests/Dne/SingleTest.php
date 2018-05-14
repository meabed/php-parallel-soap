<?php

namespace Tests\Dne;

use Tests\Base\BaseDneCase;

class SingleTest extends BaseDneCase
{

    public function __construct($name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->parallelSoapClient->setMulti(false);
    }

    public function testAddInteger()
    {
        $data = [
            'intA' => 4, 'intB' => 3,
        ];
        $rs = $this->parallelSoapClient->Add($data);
        $this->assertEquals('7', $rs);
    }

    public function testInvalidMethod()
    {
        // is not a valid method
        try {
            $this->parallelSoapClient->AddUnkown('demo', '123456');
        } catch (\Exception $e) {
            $this->assertEquals(\SoapFault::class, get_class($e));
            $this->assertContains('Function ("AddUnkown") is not a valid method for this service', $e->getMessage());
        }
    }

    public function testInvalidParam()
    {
        // invalid params exception
        try {
            $this->parallelSoapClient->Add(['a' => 1]);
        } catch (\Exception $e) {
            $this->assertEquals(\SoapFault::class, get_class($e));
            $this->assertContains('object has no \'intA\' property', $e->getMessage());
        }
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