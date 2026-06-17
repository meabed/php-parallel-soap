<?php

/**
 * Router script for PHP's built-in web server (`php -S`).
 *
 * It answers SOAP calls for the Calculator service over plain HTTP. The special
 * "Crash" operation is short-circuited with a non-XML body so the test suite can
 * exercise the client's malformed-response handling.
 */

require __DIR__ . '/CalculatorService.php';

use Tests\Fixtures\CalculatorService;

$body = file_get_contents('php://input');

// Simulate a misbehaving backend that returns a non-SOAP/non-XML body.
if (is_string($body) && str_contains($body, 'Crash')) {
    http_response_code(400);
    header('Content-Type: text/plain');
    echo 'Bad Request - this is not an XML document';
    return;
}

$server = new SoapServer(__DIR__ . '/calculator.wsdl');
$server->setClass(CalculatorService::class);
$server->handle();
