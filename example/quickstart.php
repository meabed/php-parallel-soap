<?php

/**
 * Quick-start template for meabed/php-parallel-soap.
 *
 * Point $wsdl at your own SOAP service and run:
 *
 *     php example/quickstart.php
 *
 * For a fully runnable, offline example see the hermetic test suite in tests/Hermetic,
 * which boots a local SOAP server (HTTP and TLS) and calls it through this client.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Meabed\ParallelSoap\ParallelSoapClient;

/** @var string $wsdl Replace with the WSDL of the service you want to call. */
$wsdl = 'https://example.com/service?wsdl';

$options = [
    'trace' => true,
    'exceptions' => true,
    'soap_version' => SOAP_1_1,
    'cache_wsdl' => WSDL_CACHE_BOTH,
    'encoding' => 'UTF-8',
    // Optional: unwrap the "<MethodResult>" envelope into a scalar/object value.
    'resFn' => static fn ($method, $res) => $res->{$method . 'Result'} ?? $res,
];

$client = new ParallelSoapClient($wsdl, $options);

/* -------------------------------------------------------------------------
 * 1) Synchronous call — behaves exactly like the native SoapClient.
 * ---------------------------------------------------------------------- */
$client->setMulti(false);

try {
    $result = $client->SomeMethod(['arg1' => 1, 'arg2' => 2]);
    echo 'Sync result: ' . print_r($result, true) . "\n";
} catch (SoapFault $ex) {
    echo 'SoapFault: ' . $ex->faultcode . ' - ' . $ex->getMessage() . "\n";
}

/* -------------------------------------------------------------------------
 * 2) Parallel calls — queue many requests, then run() them concurrently.
 *    In parallel mode each call returns a request id instead of a result,
 *    and run() returns an array keyed by those ids.
 * ---------------------------------------------------------------------- */
$client->setMulti(true);
$client->setCurlOptions([
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => true,
]);

$requestIds = [];
for ($i = 0; $i < 5; $i++) {
    $requestIds[] = $client->SomeMethod(['arg1' => $i, 'arg2' => $i + 1]);
}

// Fire all queued requests at once. The client resets to single mode afterwards.
$responses = $client->run();

foreach ($responses as $id => $response) {
    if ($response instanceof SoapFault) {
        // In parallel mode faults are returned, not thrown.
        echo "Error {$id}: {$response->getMessage()}\n";
        continue;
    }
    echo "OK {$id}: " . (is_string($response) ? $response : json_encode($response)) . "\n";
}
