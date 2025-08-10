# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2025-08-10
### Added
- Initial release
- ResponseBuilder for standard responses (success, error, paginated)
- StreamResponseBuilder for file downloads and streaming responses
- Formatters: JSON, Text, XML, CSV, NDJSON
- Content negotiation via Accept header
- Problem Details (RFC 9457) support
- PSR-15 middleware for content negotiation and exception handling
- Framework integration examples for Laravel, Symfony, and Slim