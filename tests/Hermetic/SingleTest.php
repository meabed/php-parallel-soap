<?php

namespace Tests\Hermetic;

class SingleTest extends HermeticTestCase
{
    public function testAdd(): void
    {
        $client = $this->newClient(false);
        $this->assertEquals('7', $client->Add(['intA' => 4, 'intB' => 3]));
    }

    public function testSubtract(): void
    {
        $client = $this->newClient(false);
        $this->assertEquals('6', $client->Subtract(['intA' => 10, 'intB' => 4]));
    }

    public function testMultiply(): void
    {
        $client = $this->newClient(false);
        $this->assertEquals('20', $client->Multiply(['intA' => 4, 'intB' => 5]));
    }

    public function testDivide(): void
    {
        $client = $this->newClient(false);
        $this->assertEquals('4', $client->Divide(['intA' => 8, 'intB' => 2]));
    }

    public function testDivideByZeroThrowsSoapFault(): void
    {
        $client = $this->newClient(false);

        $this->expectException(\SoapFault::class);
        $this->expectExceptionMessageMatches('/Division by zero/i');
        $client->Divide(['intA' => 8, 'intB' => 0]);
    }

    public function testInvalidMethodThrowsSoapFault(): void
    {
        $client = $this->newClient(false);

        try {
            $client->DoesNotExist(['intA' => 1, 'intB' => 2]);
            $this->fail('Expected a SoapFault for an unknown method.');
        } catch (\SoapFault $e) {
            $this->assertStringContainsString(
                'Function ("DoesNotExist") is not a valid method for this service',
                $e->getMessage()
            );
        }
    }

    public function testMalformedResponseThrowsSoapFault(): void
    {
        $client = $this->newClient(false);

        try {
            $client->Crash([]);
            $this->fail('Expected a SoapFault for a non-XML response.');
        } catch (\SoapFault $e) {
            $this->assertStringContainsString('looks like we got no XML document', $e->getMessage());
        }
    }
}
