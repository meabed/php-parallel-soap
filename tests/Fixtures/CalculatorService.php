<?php

namespace Tests\Fixtures;

/**
 * Backend implementation handled by the hermetic SOAP test servers.
 *
 * Each method receives the document/literal wrapper element as a single object
 * (e.g. $params->intA, $params->intB) and returns an associative array whose key
 * matches the `<OperationResult>` element declared in calculator.wsdl.
 */
class CalculatorService
{
    public function Add($params): array
    {
        return ['AddResult' => (int)$params->intA + (int)$params->intB];
    }

    public function Subtract($params): array
    {
        return ['SubtractResult' => (int)$params->intA - (int)$params->intB];
    }

    public function Multiply($params): array
    {
        return ['MultiplyResult' => (int)$params->intA * (int)$params->intB];
    }

    public function Divide($params): array
    {
        if ((int)$params->intB === 0) {
            throw new \SoapFault('Client', 'Division by zero is not allowed');
        }

        return ['DivideResult' => intdiv((int)$params->intA, (int)$params->intB)];
    }

    /**
     * Never reached through SoapServer: the test servers short-circuit the "Crash"
     * operation and answer with a non-XML body to exercise the client's
     * "looks like we got no XML document" error path.
     */
    public function Crash($params): array
    {
        return ['CrashResult' => 'unreachable'];
    }
}
