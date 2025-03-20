# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [1.1.0-rc1] - unreleased

### Added

- support Nextcloud 29-31

- promote the current wiki page path in the browser url, that way a
  page reload will not lead to the wiki start page, but instead also
  reload the current wiki view.

### Changed

- drop support for Nextcloud <= v28

- translations, thanks to the language team

- drop jQuery, convert everything to vue

## [1.0.3] - 2024-03-23

### Fixed

- restore PHP 8.1 compatibility

## [1.0.2] - 2024-03-17

### Added

- support Nextcloud 28

### Changed

- drop support for Nextcloud <= v25

- translations

## [1.0.1] - 2023-08-03

### Added

- Nextcloud 26/27 support

## [1.0.0] - 2023-03-01

### Fixed

- spelling errors

### Added

- move a PHP library shared also by other apps into its own
  app-private namespace

## [1.0.0-rc2] - 2023-02-08

### Fixed

- cookie-path tweaking if NC is installed top-level
- login-listener removed

### Added

- screenshots

## [1.0.0-rc1] - 2023-02-08

### Added

- First pre-release
