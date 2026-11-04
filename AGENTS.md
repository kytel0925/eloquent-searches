# AGENTS.md

Guidance for AI coding agents working on this repository. Humans should start with `README.md` and `CONTRIBUTING.md`.

## What this is

`sloth-dev-guy/searches` is a Laravel package (PHP 8.2+, Laravel 11–13).
It translates an array of conditions, usually an HTTP query string, into Eloquent query builder calls: wheres, relationship joins, nested groups, order, group and pagination.

## Commands

```bash
composer install
composer test        # PHPUnit (Orchestra Testbench, in-memory SQLite)
composer lint        # Pint --test
composer format      # Pint (fix)
composer analyse     # PHPStan + Larastan, level 5
composer check       # all of the above
```

Run `composer format` and `composer test` before every commit. Don't commit `composer.lock`: this is a library.

## Layout

```
src/
  Search.php                      Default Searcher: options, pagination, count; maps each key to a SearchBuilder
  Searcher.php                    Searcher contract
  SearchBuilder.php               Contract: one condition key -> query builder calls
  SearchFactory.php               Service behind the Searches facade and eloquent_search()
  EloquentSearch.php              Model trait: search(), newSearch(), allow lists
  EloquentSearchServiceProvider.php
  helpers.php                     eloquent_search(), morph_model_alias()
  Facades/Searches.php
  Support/SearchGuard.php         Security checks for columns, relationships, aliases and morph types
  Support/Fields.php              Column qualification
  Exceptions/                     InvalidSearchCondition (implements SearchException)
  Where/                          SearchWhereBuilder + one class per family of where methods
    WhereArguments.php            Parses "field|arg|arg" keys
  Join/                           SearchJoinRelationshipBuilder + one adapter per relationship type
  Http/                           SearchRequest (FormRequest), SearchAction, MergesRouteParameters
  ResponseModels/, Interfaces/    Response schemas
config/searches.php               Merged under the "searches" key
tests/
  Fixtures/Models, Fixtures/migrations
  Security/                       Guard tests: keep and extend them
```

## How a search runs

1. `Search::conditions()` maps each `key => value` pair.
   `SearchJoinRelationshipBuilder` is tried first, then `SearchWhereBuilder`.
   A builder returns `null` when the key isn't for it, and throws `InvalidSearchCondition` when the key is for it but invalid.
   If no builder accepts the key, `Search` throws `InvalidSearchCondition::unsupportedKey()`.
2. A join pushes the join clauses, then creates a nested `Search` over the related model, with the value as its conditions and the join alias as `from_alias`.
3. A where parses its key with `WhereArguments`, picks a `BaseWhere` subclass by method name (`redirect()` can switch it, for example `where` to `whereIn` for arrays), and checks every column through the guard.
4. `get()` applies select, distinct, order and group, stores the unpaginated builder for `count()`, then paginates.

## Rules that must hold

* **Conditions are untrusted input.** Every column, alias, relationship and morph type that comes from a key or value must go through `Support\SearchGuard` before it reaches the query builder:
  * `assertSearchable()` for condition columns, `assertSortable()` for order and group columns, `relation()` for relationships, `assertAlias()`, `morphModel()`.
  * Never call a model method chosen by input except through `SearchGuard::relation()`.
  * Never instantiate a class named by input except through `SearchGuard::morphModel()`.
  * New key arguments must be explicit: add them to the lists in `WhereArguments`. Unknown arguments must keep throwing.
  * A new `BaseWhere` subclass must return from `columns()` every column it references, including columns passed as values.
* **Errors are exceptions, not logs.** Throw `InvalidSearchCondition` with a message that is safe to show to API clients. `SearchAction` converts it into a 422. Return `['method' => null]` from `where()` only for intentional skips (an empty full text value, `field|null => false`).
* **No global state.** Core classes must not read `request()`; HTTP concerns belong in `Http/`. Read configuration with `config('searches.*')`, and add new keys (with defaults) to `config/searches.php`.
* **Keep the supported range.** Code must run on PHP 8.2 and Laravel 11, 12 and 13. Don't use APIs missing from Laravel 11.
* Type every parameter and return value, and use `?Type $x = null` (never implicit nullable).

## Tests

* Every bug fix needs a regression test. Every security-relevant change needs a test in `tests/Security`.
* Use the fixture models in `tests/Fixtures/Models`. Relationships must declare return types: the guard depends on them.
* `Person::$calls` records calls to `Person::archive()`. Use it to prove a key never calls a model method.
* `RestrictedPerson` has allow lists, and `Note` uses the trait with a hidden `secret` column.
* Where and join unit tests mock `Searcher`; integration tests use real models on SQLite.
  SQLite can't compile full text queries, so test those through the `where()` payload.

## Conventions

* Code style: Laravel Pint (`pint.json`).
* Commit messages: `{gitmoji} {type}({module}): {description}` (see the table in `CONTRIBUTING.md`).
  Don't add AI attribution to commits.
* Keep the README, `CHANGELOG.md` (`Unreleased`) and this file in the same commit as the change that requires them.
