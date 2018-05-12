.DEFAULT_GOAL := help

help:
	@echo ""
	@echo "Available tasks:"
	@echo "    server               Run php local server"
	@echo "    deps-update-dev      Install dependencies"
	@echo "    deps-update-prod     Install dependencies"
	@echo "    deps-install-dev     Install dependencies"
	@echo "    deps-install-prod    Install dependencies"
	@echo "    lint                 Run linter and code style checker"
	@echo "    unit                 Run unit tests and generate"
	@echo "    unit-cov             Run unit tests and generate coverage"
	@echo "    unit-html            Run unit tests and generate coverage, html"
	@echo "    test                 Run linter and unit tests"
	@echo "    watch                Run linter and unit tests when any of the source files change"
	@echo "    all                  Install dependencies and run linter and unit tests"
	@echo ""

deps-update-dev:
	composer update --prefer-dist --no-ansi --no-interaction --optimize-autoloader --ignore-platform-reqs

deps-update-prod:
	composer update --prefer-dist --no-ansi --no-interaction --optimize-autoloader --ignore-platform-reqs --no-dev

deps-install-dev:
	composer install --prefer-dist --no-ansi --no-interaction --optimize-autoloader --ignore-platform-reqs

deps-install-prod:
	composer install --prefer-dist --no-ansi --no-interaction --optimize-autoloader --ignore-platform-reqs --no-dev

lint:
	vendor/bin/phplint . --exclude=vendor/ --exclude=tests/
	vendor/bin/phpcs -p --standard=PSR2 --extensions=php --encoding=utf-8 --ignore=*/tests/*,*/vendor/*,*/benchmarks/* .

csfix:
	vendor/bin/phpcbf -p --standard=PSR2 --extensions=php --encoding=utf-8 --ignore=*/tests/*,*/vendor/*,*/benchmarks/* .

unit:
	vendor/bin/phpunit

unit-cov:
	vendor/bin/phpunit --coverage-text --coverage-clover=coverage.xml

unit-html:
	vendor/bin/phpunit --coverage-text --coverage-clover=coverage.xml --coverage-html=./report/

test: lint unit

travis: lint unit

all: deps-install-dev test

.PHONY: help deps-update-dev deps-update-prod deps-install-dev deps-install-prod lint unit unit-html test travis all