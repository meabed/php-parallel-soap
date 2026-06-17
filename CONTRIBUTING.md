# Contributing

## Only one feature or change per pull request

Make pull requests only one feature or change at the time. Make pull requests from feature branch. Pull requests should not come from your master branch.

For example you have fixed a bug. You also have optimized some code. Optimization is not related to a bug. These should be submitted as separate pull requests. This way I can easily choose what to include. It is also easier to understand the code changes.

## Write meaningful commit messages

Proper commit message is full sentence. It starts with capital letter but does not end with period. Headlines do not end with period. The GitHub default `Update filename.js` is not enough. When needed include also longer explanation what the commit does.

```
Capitalized, short (50 chars or less) summary

More detailed explanatory text, if necessary.  Wrap it to about 72
characters or so.  In some contexts, the first line is treated as the
subject of an email and the rest of the text as the body.  The blank
line separating the summary from the body is critical (unless you omit
the body entirely); tools like rebase can get confused if you run the
two together.
```

When in doubt see Tim Pope's blogpost [A Note About Git Commit Messages](http://tbaggery.com/2008/04/19/a-note-about-git-commit-messages.html)

## Send coherent history

Make sure each individual commit in your pull request is meaningful. If you had to make multiple intermediate commits while developing, please squash them before submitting.

## Follow the existing coding standards

When contributing to open source project it is polite to follow the original authors coding standars. They might be different than yours. It is not a holy war. This project uses **[PSR-2 Coding Standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md)**

## Running tests

Install the dependencies and run the checks:

```bash
composer install

composer lint          # PHP syntax lint
composer check-style   # PSR-2 coding standard
composer stan          # PHPStan static analysis
composer test          # hermetic test suite (no network required)
composer ci            # all of the above
```

The default test run is fully **hermetic** — it starts a local SOAP server (over HTTP and
TLS) and needs no internet access. The external integration tests that hit public demo
services are opt-in:

```bash
vendor/bin/phpunit --group external
```

Please make sure `composer ci` passes before opening a pull request.