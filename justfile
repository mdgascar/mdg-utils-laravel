# mdg-utils-laravel: every command in one place. Run `just` to list them.
set shell := ["bash", "-euo", "pipefail", "-c"]

# List commands
default:
    @just --list

# Install dependencies
setup:
    composer install

# Coding standard, static analysis, magic numbers
lint:
    composer lint

# Fix coding standard
format:
    composer cs-fix

# Run tests with 100% coverage
test:
    composer test

# Everything CI runs
ci: lint test
