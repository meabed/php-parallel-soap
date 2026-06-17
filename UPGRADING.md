# Upgrading

## 3.x → 4.0

### The namespace changed

PHP 8.4 reserved the top-level `Soap\` namespace for the SOAP extension (it now ships
`Soap\Url` and `Soap\Sdl`). To stay out of its way, the client moved to its own vendor
namespace.

| Before (3.x)              | After (4.0)                            |
| ------------------------- | -------------------------------------- |
| `Soap\ParallelSoapClient` | `Meabed\ParallelSoap\ParallelSoapClient` |

Update your imports:

```diff
-use Soap\ParallelSoapClient;
+use Meabed\ParallelSoap\ParallelSoapClient;
```

If you reference the client across many files, this one-liner rewrites the imports in place
(macOS `sed`; on Linux use `sed -i` without the `''`):

```bash
grep -rl 'Soap\\ParallelSoapClient' src \
  | xargs sed -i '' 's/Soap\\ParallelSoapClient/Meabed\\ParallelSoap\\ParallelSoapClient/g'
```

The class name (`ParallelSoapClient`), its public API, constructor options and behaviour are
all unchanged — only the namespace moved.

### Requirements

The runtime requirements are unchanged (PHP 8.0+, `ext-soap`, `ext-curl`, `ext-libxml`).

> If you were on a 3.x release and running PHP 8.4 or 8.5, the client could not even be
> autoloaded (an incompatible `__doRequest()` signature caused a fatal error). 4.0 fixes that;
> upgrading is required on those PHP versions.
