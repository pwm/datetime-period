# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [1.0.1] - 2018-04-09
### Changed
  * Renamed the misleading `TimeZoneMismatch` exception to `UTCOffsetMismatch`.

### Removed
  * The interval property. The reason was that it can trivially be derived from the start/end instants and having it made the data type smart which created problems. In general keeping the data type dumb is preferred.

## [1.0.0] - 2017-10-10
### Added
  * Initial release
