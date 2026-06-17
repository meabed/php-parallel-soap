<?php

namespace Tests\Support;

/**
 * Boots the hermetic Calculator SOAP service over TLS/HTTPS.
 *
 * A self-signed certificate is generated on the fly and a small TLS-terminating
 * server (Fixtures/tls-server.php) answers SOAP calls, so the curl/TLS code path
 * of the client is exercised without any external dependency.
 */
final class TlsSoapTestServer
{
    /** @var resource */
    private $process;

    /** @var array<int, resource> */
    private array $pipes = [];

    private string $certFile;

    public string $url;

    private function __construct()
    {
    }

    public static function isSupported(): bool
    {
        return extension_loaded('openssl') && function_exists('proc_open');
    }

    public static function start(): self
    {
        $self = new self();
        $port = self::findFreePort();
        $self->certFile = self::generateSelfSignedCertificate();
        $server = dirname(__DIR__) . '/Fixtures/tls-server.php';

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open(
            [PHP_BINARY, '-d', 'opcache.enable_cli=0', $server, (string)$port, $self->certFile],
            $descriptors,
            $self->pipes,
            null,
            getenv()
        );

        if (!is_resource($process)) {
            throw new \RuntimeException('Unable to start the TLS SOAP test server.');
        }

        $self->process = $process;
        $self->url = "https://127.0.0.1:$port/soap";

        $self->waitUntilReady();

        return $self;
    }

    public function certPath(): string
    {
        return $this->certFile;
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
        if (isset($this->certFile) && is_file($this->certFile)) {
            @unlink($this->certFile);
        }
    }

    private function waitUntilReady(float $timeout = 5.0): void
    {
        $stdout = $this->pipes[1];
        stream_set_blocking($stdout, false);
        $deadline = microtime(true) + $timeout;

        while (microtime(true) < $deadline) {
            $line = fgets($stdout);
            if ($line !== false && str_contains($line, 'ready')) {
                return;
            }
            $status = proc_get_status($this->process);
            if (!$status['running']) {
                $err = stream_get_contents($this->pipes[2]);
                throw new \RuntimeException('TLS SOAP test server exited early: ' . trim((string)$err));
            }
            usleep(50_000);
        }

        throw new \RuntimeException('The TLS SOAP test server did not become ready in time.');
    }

    private static function generateSelfSignedCertificate(): string
    {
        $dn = [
            'commonName' => '127.0.0.1',
            'organizationName' => 'php-parallel-soap-tests',
        ];

        $privateKey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        $csr = openssl_csr_new($dn, $privateKey, ['digest_alg' => 'sha256']);
        $x509 = openssl_csr_sign($csr, null, $privateKey, 365, ['digest_alg' => 'sha256']);

        openssl_x509_export($x509, $certPem);
        openssl_pkey_export($privateKey, $keyPem);

        $file = tempnam(sys_get_temp_dir(), 'pps_tls_') . '.pem';
        file_put_contents($file, $certPem . $keyPem);

        return $file;
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
}
