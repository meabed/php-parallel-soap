<h1 align="center">Parallel, Multi-Curl PHP SoapClient</h1>

<p align="center">
  <a href="https://github.com/meabed/php-parallel-soap/actions/workflows/ci.yml">
    <img src="https://github.com/meabed/php-parallel-soap/actions/workflows/ci.yml/badge.svg" alt="CI Status">
  </a>
  <a href="https://packagist.org/packages/meabed/php-parallel-soap">
    <img src="https://img.shields.io/packagist/v/meabed/php-parallel-soap.svg?style=flat-square" alt="Latest Version">
  </a>
  <a href="https://packagist.org/packages/meabed/php-parallel-soap">
    <img src="https://img.shields.io/packagist/php-v/meabed/php-parallel-soap.svg?style=flat-square" alt="PHP Version">
  </a>
  <a href="https://packagist.org/packages/meabed/php-parallel-soap">
    <img src="https://img.shields.io/packagist/dm/meabed/php-parallel-soap.svg?style=flat-square" alt="Total Downloads">
  </a>
  <a href="LICENSE.md">
    <img src="https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square" alt="Software License">
  </a>
</p>

A drop-in replacement for PHP's native `SoapClient` that can fire **many SOAP requests in
parallel** using curl's multi handle, while keeping the familiar synchronous SoapClient API
when you need it.

Working with SOAP is often painful:

- SOAP messages are verbose and obscure.
- Performance suffers because the native client has no connection pooling, TLS session
  reuse, or low-level TCP tuning.
- A list of calls can only be sent sequentially — you loop and wait for each response.
- Debugging the underlying HTTP (headers / payloads / errors) is hard.

`ParallelSoapClient` lets you send requests concurrently and gives you hooks for a PSR-3
logger, response parsing, custom `SOAPAction` headers, XML formatting and arbitrary curl
options (such as TLS session sharing and connection reuse).

## Features

- **Single and parallel modes** — switch with `setMulti(true|false)`.
- **True concurrency** via `curl_multi_exec` — consecutive calls no longer block each other.
- **TLS / DNS / cookie session sharing** through a shared curl handle per endpoint.
- **Per-request curl metadata** captured in `$client->curlInfo`.
- **Deterministic request ids** — identical payloads hash to the same id, so duplicate calls
  are sent only once.
- **First-class exception handling** in both single and parallel modes.
- **Pluggable hooks** — logger, result parser, `SOAPAction` builder, XML formatter, debug
  callback.

## Requirements

- PHP **8.0+**
- `ext-soap`, `ext-curl`, `ext-libxml`

The library is tested in CI against PHP **8.1, 8.2, 8.3, 8.4 and 8.5** (including a TLS suite).

> **Upgrading from 3.x?** 4.0 moves the client out of the now-reserved `Soap\` namespace into
> `Meabed\ParallelSoap\`. Change `use Soap\ParallelSoapClient;` to
> `use Meabed\ParallelSoap\ParallelSoapClient;` — the API is otherwise unchanged. 3.x also
> fatal-errors on PHP 8.4/8.5, so upgrading is required there. See [UPGRADING.md](UPGRADING.md)
> and the [CHANGELOG](CHANGELOG.md).

## Installation

```bash
composer require meabed/php-parallel-soap
```

## Usage

### Synchronous (single) call

Behaves exactly like the native `SoapClient`, but the transport is curl:

```php
use Meabed\ParallelSoap\ParallelSoapClient;

$client = new ParallelSoapClient($wsdl, [
    'trace' => true,
    'exceptions' => true,
    'soap_version' => SOAP_1_1,
    // Optional: unwrap the "<MethodResult>" envelope into a scalar value.
    'resFn' => fn ($method, $res) => $res->{$method . 'Result'} ?? $res,
]);

$client->setMulti(false); // default

$sum = $client->Add(['intA' => 4, 'intB' => 3]); // 7
```

### Parallel (multi) call

Queue any number of calls, then execute them all at once with `run()`:

```php
$client->setMulti(true);
$client->setCurlOptions([
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => true,
]);

// Each call returns a request id instead of a result while in parallel mode.
$id1 = $client->Add(['intA' => 4,  'intB' => 3]);
$id2 = $client->Add(['intA' => 10, 'intB' => 20]);
$id3 = $client->Add(['intA' => 10, 'intB' => 20]); // identical payload => same id as $id2

// Fire every queued request concurrently. The client resets to single mode afterwards.
$responses = $client->run();

echo $responses[$id1]; // 7
echo $responses[$id2]; // 30
```

### Handling errors in parallel mode

In parallel mode exceptions are **not** thrown; instead each result is either the parsed
response or a `SoapFault` object, so you inspect the result type:

```php
$responses = $client->run();

foreach ($responses as $id => $response) {
    if ($response instanceof SoapFault) {
        // Network error, malformed response, server fault, ...
        echo "Error for {$id}: {$response->getMessage()}\n";
        continue;
    }
    echo "OK {$id}: {$response}\n";
}
```

Calling a method that does not exist in the WSDL short-circuits before any HTTP request and
returns a string prefixed with `ParallelSoapClient::ERROR_STR` (`*ERROR*`).

### Running a subset of queued requests

```php
$responses = $client->run([$id1, $id3]); // only execute these two
```

## Configuration

All hooks are passed through the constructor `$options` array (and are removed before being
handed to the native `SoapClient`):

| Option         | Type            | Purpose                                                                 |
| -------------- | --------------- | ----------------------------------------------------------------------- |
| `logger`       | `Psr\Log\LoggerInterface` | PSR-3 logger; defaults to `NullLogger`.                      |
| `resFn`        | `callable($method, $res)` | Transform/unwrap each parsed response.                        |
| `soapActionFn` | `callable($action, $headers)` | Build the outgoing `SOAPAction` header(s).               |
| `formatXmlFn`  | `callable($xml)` | Format the request XML before it is logged.                            |
| `debugFn`      | `callable($res, $id)` | Receive each response/exception for debugging or metadata capture. |

Plus the runtime setters:

- `setMulti(bool)` — toggle parallel mode.
- `setCurlOptions(array)` — any `CURLOPT_*` options (TLS, timeouts, proxies, verbosity…).
- `setLogSoapRequest(bool)` — log every request payload through the PSR-3 logger.

See the [`example/`](example) directory for complete, runnable scripts.

## How it works

`__doRequest()` is overridden to build a curl handle per call instead of sending the request
immediately. In parallel mode the handles are accumulated and executed together through
`curl_multi_*`; the raw responses are then fed back through the native SOAP parser so you get
ordinary PHP objects (or `SoapFault`s) out the other end. A shared curl handle per endpoint
enables TLS session, DNS and cookie reuse across the batch.

For a deeper walkthrough — the request lifecycle, the local SOAP server used by the tests, and
diagrams — see [docs/development.md](docs/development.md).

## Testing

The test suite has two layers:

- **Hermetic tests** (`tests/Hermetic`) run against a local SOAP server started on a free
  port — over both **HTTP** and **TLS** (with a generated self-signed certificate). They need
  no network access and run by default.
- **External integration tests** (`tests/Crcind`, `tests/Dne`) hit public demo SOAP services.
  They are tagged with the `external` group, excluded from the default run, and skip
  automatically when the host is unreachable.

```bash
composer install

composer test          # hermetic suite only (default)
composer check-style   # PSR-2 coding standard
composer stan          # PHPStan static analysis
composer lint          # php-l syntax lint
composer ci            # lint + style + stan + tests

vendor/bin/phpunit --group external   # opt into the external integration tests
```

You can also run the local SOAP server on its own with `composer dev-server` and call it
directly — see [docs/development.md](docs/development.md) for a worked example.

## Contributing

Contributions are welcome — please review the [guidelines](CONTRIBUTING.md):

- [One feature or change per pull request](CONTRIBUTING.md#only-one-feature-or-change-per-pull-request)
- [Write meaningful commit messages](CONTRIBUTING.md#write-meaningful-commit-messages)
- [Follow the existing coding standards](CONTRIBUTING.md#follow-the-existing-coding-standards)

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a list of notable changes.

## License

Released under the [MIT license](LICENSE.md).
