# Changelog

All notable changes to this project are documented here.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project follows [Semantic Versioning](https://semver.org/).

## [Unreleased]

This release hardens the package for untrusted input and modernizes it as a Laravel package. It contains breaking changes.

### Security

- A condition key matching a public model method no longer calls it. Only methods with a `Relation`
  return type, or listed in the model's `$searchableRelations`, are treated as relationships.
- Columns in the model's `$hidden` property can't be searched, sorted or grouped.
- New optional allow lists on models (`$searchable`, `$sortable`, `$searchableRelations`) and a strict mode (`searches.strict`).
- Column names, table prefixes and aliases are validated, and unknown key arguments are rejected.
- `relation:type` on morph-to joins only accepts morph map aliases and model classes; it no longer instantiates any class.
- `SearchRequest` limits `max` to `searches.request.max_per_page` (100 by default).

### Added

- `SearchAction::handle(Model|string $from)`.
- `SearchRequest::searchOptions()` and the `MergesRouteParameters` trait (`routeParameterKeys()`).
- `InvalidSearchCondition` exception (implements `SearchException`, with `key()`); `SearchAction` reports it as a 422 validation error.
- The configuration is merged and publishable (`php artisan vendor:publish --tag=searches-config`).
  Where builders, join types and relationship adapters are now configurable.
- `order` accepts a `[column => direction]` map, and `group` accepts comma separated columns.

### Fixed

- Unsupported keys (for example `name|where-raw`) crashed with "Call to a member function on null".
- `SearchRequest::group()` crashed on comma separated values (`?group=a,b`).
- `eloquent_search()` and the `Search` constructor accept a model class name.
- `count()` works before `get()`, and counts correctly with `distinct` and `group`.
- `field|in` with a single value now builds a plain `where`.
- Full text values with exactly the minimum length (3) were ignored.
- `field|nest|or` ignored the `or`.
- Curly bracket fields (`{field}`) were also parsed as a flag argument.
- Implicitly nullable parameters (deprecated in PHP 8.4).

### Changed (breaking)

- Requires PHP 8.2+ and Laravel 11, 12 or 13. Lumen and Laravel 10 are no longer supported.
- `Search` no longer reads `page`, `max`, `order`, `group` and `distinct` from the current request.
  Pass them as options, or use `SearchAction`.
- `SearchRequest` extends `FormRequest`, and `all()` returns the whole input; use `getConditions()` for the conditions.
- `RequestValidate` and `MergeRouteArguments` were removed (replaced by `FormRequest` and `MergesRouteParameters`).
- Invalid conditions throw `InvalidSearchCondition` instead of being logged and skipped. Invalid `between` values,
  scalar values for relationship joins, and morph-to joins without a type are now rejected too.
- Order and group columns are qualified with the table name, and `whereColumn` qualifies both columns.
- The `Searcher`, `SearchBuilder` and `WhereBuilder` contracts have typed signatures.
  `Searcher` no longer declares static methods, and `WhereBuilder` gained `columns()`.
- The global `warning()` helper was removed.
- The package no longer writes to the application log.
