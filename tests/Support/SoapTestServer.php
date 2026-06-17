<?php

namespace Tests\Support;

/**
 * Boots the hermetic Calculator SOAP service on PHP's built-in web server.
 *
 * The server runs in a child process on a free localhost port so the test suite
 * never depends on any external/network SOAP endpoint.
 */
final class SoapTestServer
{
    /** @var resource */
    private $process;

    /** @var array<int, resource> */
    private array $pipes = [];

    public string $url;

    private function __construct()
    {
    }

    public static function start(): self
    {
        $self = new self();
        $port = self::findFreePort();
        $docRoot = dirname(__DIR__) . '/Fixtures';
        $router = $docRoot . '/http-server.php';

        $env = getenv();
        // Allow the built-in server to handle the parallel requests concurrently.
        $env['PHP_CLI_SERVER_WORKERS'] = '4';

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open(
            [PHP_BINARY, '-d', 'opcache.enable_cli=0', '-S', "127.0.0.1:$port", $router],
            $descriptors,
            $self->pipes,
            $docRoot,
            $env
        );

        if (!is_resource($process)) {
            throw new \RuntimeException('Unable to start the built-in SOAP test server.');
        }

        $self->process = $process;
        $self->url = "http://127.0.0.1:$port/soap";

        self::waitUntilListening('127.0.0.1', $port);

        return $self;
    }

    public function stop(): void
    {
        if (isset($this->process) && is_resource($this->process)) {
            proc_terminate($this->process);
            foreach ($this->pipes as $pipe) {
                if (is_resource($pipe)) {
                    fclose($pipe);
                }
            }
            proc_close($this->process);
        }
    }

    private static function findFreePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        if (!$socket) {
            throw new \RuntimeException("Unable to allocate a free port: $errstr ($errno)");
        }
        $name = stream_socket_get_name($socket, false);
        fclose($socket);

        return (int)substr($name, strrpos($name, ':') + 1);
    }

    private static function waitUntilListening(string $host, int $port, float $timeout = 5.0): void
    {
        $deadline = microtime(true) + $timeout;
        while (microtime(true) < $deadline) {
            $conn = @fsockopen($host, $port, $errno, $errstr, 0.2);
            if ($conn) {
                fclose($conn);
                return;
            }
            usleep(50_000);
        }

        throw new \RuntimeException("The SOAP test server did not start listening on $host:$port.");
    }
}
