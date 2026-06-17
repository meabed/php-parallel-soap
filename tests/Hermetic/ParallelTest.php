<?php

namespace Tests\Hermetic;

use Meabed\ParallelSoap\ParallelSoapClient;

class ParallelTest extends HermeticTestCase
{
    public function testParallelRequests(): void
    {
        $client = $this->newClient(true);

        $req1 = $client->Add(['intA' => 4, 'intB' => 3]);
        $req2 = $client->Subtract(['intA' => 10, 'intB' => 4]);
        $req3 = $client->Multiply(['intA' => 4, 'intB' => 5]);

        $responses = $client->run();

        $this->assertEquals('7', $responses[$req1]);
        $this->assertEquals('6', $responses[$req2]);
        $this->assertEquals('20', $responses[$req3]);
    }

    public function testDuplicateRequestsShareOneHandle(): void
    {
        $client = $this->newClient(true);

        $req1 = $client->Add(['intA' => 3, 'intB' => 5]);
        $req2 = $client->Add(['intA' => 3, 'intB' => 5]); // identical payload -> same hash id

        $this->assertSame($req1, $req2);

        $responses = $client->run();

        $this->assertEquals('8', $responses[$req1]);
        $this->assertEquals('8', $responses[$req2]);
    }

    public function testInvalidMethodReturnsErrorString(): void
    {
        $client = $this->newClient(true);

        $result = $client->DoesNotExist(['intA' => 1, 'intB' => 2]);

        $this->assertStringContainsString(ParallelSoapClient::ERROR_STR, $result);
        $this->assertStringContainsString('is not a valid method for this service', $result);
    }

    public function testMalformedResponseYieldsSoapFaultInResults(): void
    {
        $client = $this->newClient(true);

        $ok = $client->Add(['intA' => 4, 'intB' => 3]);
        $bad = $client->Crash([]);

        $responses = $client->run();

        $this->assertEquals('7', $responses[$ok]);
        $this->assertInstanceOf(\SoapFault::class, $responses[$bad]);
        $this->assertStringContainsString('looks like we got no XML document', $responses[$bad]->getMessage());
    }

    public function testSwitchFromMultiToSingle(): void
    {
        $client = $this->newClient(true);

        $req1 = $client->Add(['intA' => 4, 'intB' => 3]);
        $responses = $client->run();
        $this->assertEquals('7', $responses[$req1]);

        // run() resets the client back to synchronous mode.
        $this->assertFalse($client->getMulti());
        $this->assertEquals('13', $client->Add(['intA' => 3, 'intB' => 10]));
    }
}
