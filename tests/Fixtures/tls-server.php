<?php

/**
 * Minimal HTTPS/TLS SOAP server used by the hermetic TLS test.
 *
 * PHP's built-in web server cannot serve TLS, so this script terminates TLS on a
 * raw stream socket with a self-signed certificate and forwards each request body
 * to a SoapServer. It only needs to understand the small subset of HTTP that the
 * client (curl) sends: a single POST with a Content-Length body per connection.
 *
 * Usage: php tls-server.php <port> <combined-cert.pem>
 */

require __DIR__ . '/CalculatorService.php';

use Tests\Fixtures\CalculatorService;

$port = (int)($argv[1] ?? 0);
$pem = $argv[2] ?? '';

if ($port <= 0 || !is_file($pem)) {
    fwrite(STDERR, "usage: php tls-server.php <port> <cert.pem>\n");
    exit(1);
}

$context = stream_context_create([
    'ssl' => [
        'local_cert' => $pem,
        'allow_self_signed' => true,
        'verify_peer' => false,
        'verify_peer_name' => false,
    ],
]);

$server = @stream_socket_server(
    "ssl://127.0.0.1:$port",
    $errno,
    $errstr,
    STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
    $context
);

if (!$server) {
    fwrite(STDERR, "bind failed: $errstr ($errno)\n");
    exit(1);
}

// Signals readiness to the parent test process.
fwrite(STDOUT, "ready\n");

while (true) {
    $conn = @stream_socket_accept($server, 30);
    if (!$conn) {
        continue;
    }

    // Read request headers up to the blank line.
    $headers = '';
    while (($line = fgets($conn)) !== false) {
        $headers .= $line;
        if (str_ends_with($headers, "\r\n\r\n") || $headers === "\r\n") {
            break;
        }
    }

    // Read the request body using the advertised Content-Length.
    $length = 0;
    if (preg_match('/Content-Length:\s*(\d+)/i', $headers, $m)) {
        $length = (int)$m[1];
    }
    $requestBody = '';
    while (strlen($requestBody) < $length) {
        $chunk = fread($conn, $length - strlen($requestBody));
        if ($chunk === false || $chunk === '') {
            break;
        }
        $requestBody .= $chunk;
    }

    // Simulate a misbehaving backend for the "Crash" operation.
    if (str_contains($requestBody, 'Crash')) {
        $payload = 'Bad Request - this is not an XML document';
        $status = '400 Bad Request';
        $contentType = 'text/plain';
    } else {
        ob_start();
        $soapServer = new SoapServer(__DIR__ . '/calculator.wsdl');
        $soapServer->setClass(CalculatorService::class);
        try {
            $soapServer->handle($requestBody);
        } catch (\Throwable $e) {
            // SoapServer already emitted a SOAP fault envelope.
        }
        $payload = ob_get_clean();
        $status = '200 OK';
        $contentType = 'text/xml; charset=utf-8';
    }

    $response = "HTTP/1.1 $status\r\n"
        . "Content-Type: $contentType\r\n"
        . 'Content-Length: ' . strlen($payload) . "\r\n"
        . "Connection: close\r\n\r\n"
        . $payload;

    fwrite($conn, $response);
    fclose($conn);
}
