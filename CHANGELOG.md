# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Fixed
- Increase default addon boostrap priority

## [1.0.0-rc.2] - 2020-07-15
### Fixed
- Use Driver::insert for one or many documents, change method signatures
  Fixes cockpit assets integration

## [1.0.0-rc.1] - 2020-03-05
### Fixed
- Allow nulls booleans in filter
- Differences in JSON path selector in MySQL

## [1.0.0-beta.2] - 2020-02-21
### Added
- Added `Collection::insertMany` method (fixes compatibility with Cockpit v0.9.3+)

## [1.0.0-beta.1] - 2019-09-16
### Added
- Initial release

[Unreleased]: https://github.com/piotr-cz/cockpit-sql-driver/compare/v1.0.0-rc.2...HEAD
[1.0.0-rc.2]: https://github.com/piotr-cz/cockpit-sql-driver/compare/v1.0.0-rc.1...v1.0.0-rc.2
[1.0.0-rc.1]: https://github.com/piotr-cz/cockpit-sql-driver/compare/v1.0.0-beta.2...v1.0.0-rc.1
[1.0.0-beta.2]: https://github.com/piotr-cz/cockpit-sql-driver/compare/v1.0.0-beta.1...v1.0.0-beta.2
[1.0.0-beta.1]: https://github.com/piotr-cz/cockpit-sql-driver/releases/tag/v1.0.0-beta.1
