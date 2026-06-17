# Changelog

All notable changes to `meabed/php-parallel-soap` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed

- **PHP 8.4 / 8.5 fatal error.** `SoapClient::__doRequest()` gained a 6th parameter
  (`?string $uriParserClass`); the incompatible override made the class fatal to autoload.
  The override now matches the parent signature. ([#179])
- **High CPU load in parallel mode.** `doRequests()` busy-waited on `curl_multi_exec()` and
  pegged the CPU at 100% while endpoints responded. It now blocks on `curl_multi_select()`
  between rounds. ([#47])
- **Proxy options were ignored.** The native SoapClient `proxy_host`, `proxy_port`,
  `proxy_login` and `proxy_password` options are now mapped onto curl so they apply to the
  parallel requests. ([#11])
- Removed the deprecated `curl_close()` call (no-op since PHP 8.0, deprecated in 8.5).
- Marked `$options` explicitly nullable and normalised `null` options, so the client can be
  constructed without an options array.
- Aligned `__soapCall()` with the parent signature and cleaned up the PHPDoc.

### Added

- **Hermetic test suite** (`tests/Hermetic`) running against a local SOAP server over both
  **HTTP** and **TLS** (self-signed certificate), requiring no network access.
- A single CI matrix covering **PHP 8.1–8.5** (plus a `--prefer-lowest` job and a weekly cron),
  running lint, PSR-2, PHPStan and the hermetic + TLS tests.
- `composer ci` script (lint + coding standards + static analysis + tests).
- `example/quickstart.php` template.

### Changed

- Updated dev dependencies: PHPUnit `^10.5 || ^11.5 || ^12 || ^13`, PHPStan `^2.1`,
  PHP_CodeSniffer `^3.13`, phplint `^9.6`.
- Required `ext-libxml`; removed the hardcoded `version` field so git tags drive releases.
- External integration tests (`crcind`, `dneonline`) are now tagged `#[Group('external')]`,
  excluded from the default test run, and skip automatically when the host is unreachable.
- Modernised `renovate.json`, `.gitattributes`, CI workflows and the README.

### Known issues

- The top-level `Soap\` namespace is now claimed by the PHP SOAP extension (`Soap\Url`,
  `Soap\Sdl` since PHP 8.4). Renaming the package namespace is a breaking change and is being
  tracked for a future major release. ([#175])

[#179]: https://github.com/meabed/php-parallel-soap/issues/179
[#175]: https://github.com/meabed/php-parallel-soap/issues/175
[#47]: https://github.com/meabed/php-parallel-soap/issues/47
[#11]: https://github.com/meabed/php-parallel-soap/issues/11
[Unreleased]: https://github.com/meabed/php-parallel-soap/compare/3.0.1...HEAD
