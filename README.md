# Eloquent Searches

Stop writing the same `if`, `where` and `join` statements in every search endpoint.

Eloquent Searches turns an array of conditions, such as an HTTP query string, into an Eloquent query.
It handles wheres, relationship joins, nested groups, sorting, grouping and pagination.
It only translates: in the end it calls the same query builder methods you would write by hand.

```php
Person::search([
    'name|_%' => 'Jo',                         // name like 'Jo%'
    'age|>=' => 18,                            // age >= 18
    'status|!' => 'banned',                    // status <> 'banned'
    'role' => ['admin', 'editor'],             // role in ('admin', 'editor')
    'created_at|between' => ['2024-01-01', '2024-12-31'],
    'tasks' => [                               // inner join tasks ...
        'title|%%' => 'report',                // ... where tasks.title like '%report%'
    ],
], ['order' => 'name,desc', 'max' => 20, 'page' => 1])->get();
```

* [Requirements](#requirements)
* [Installation](#installation)
* [Basic usage](#basic-usage)
* [Where conditions](#where-conditions)
* [Joining relationships](#joining-relationships)
* [Options: select, sort, group and paginate](#options-select-sort-group-and-paginate)
* [Security](#security)
* [HTTP endpoints](#http-endpoints)
* [Errors](#errors)
* [Configuration](#configuration)
* [Extending](#extending)
* [Upgrading](#upgrading)

## Requirements

* PHP 8.2 or newer
* Laravel 11, 12 or 13 (Laravel 13 needs PHP 8.3)

## Installation

```bash
composer require sloth-dev-guy/searches
```

The service provider is registered by package discovery.
Publishing the configuration file is optional:

```bash
php artisan vendor:publish --tag=searches-config
```

## Basic usage

There are three equivalent ways to start a search:

```php
use SlothDevGuy\Searches\EloquentSearch;

// 1. The model trait
class Person extends Model
{
    use EloquentSearch;
}

Person::search(['name' => 'Ada']);
(new Person)->newSearch(['name' => 'Ada']);

// 2. The helper
eloquent_search(Person::class, ['name' => 'Ada']);

// 3. The class itself
new \SlothDevGuy\Searches\Search(Person::class, ['name' => 'Ada']);
```

> The trait's static `search()` method conflicts with Laravel Scout's `Searchable` trait.
> If a model uses both, call `newSearch()` or the helper instead, and resolve the conflict
> with `use EloquentSearch { search as protected eloquentSearch; }`.

Every one of them returns a `SlothDevGuy\Searches\Searcher`:

```php
$search = Person::search(['name|_%' => 'A'], ['max' => 10, 'page' => 2]);

$search->get();          // Collection of the current page
$search->count();        // total results, ignoring the pagination
$search->pagination();   // ['page' => 2, 'pages' => 4, 'max' => 10, 'total' => 37]
$search->builder();      // the Eloquent builder with every condition applied
```

## Where conditions

Each key is a column name, optionally followed by arguments separated by `|`:

```php
['column|argument|argument' => $value]
```

### Operators

| Key | SQL |
| --- | --- |
| `'name' => 'Ada'` | `name = 'Ada'` |
| `'age\|>' => 18` | `age > 18` (also `<`, `>=`, `<=`, `<>`, `=`) |
| `'name\|_%' => 'Ad'` | `name like 'Ad%'` (starts with) |
| `'name\|%_' => 'da'` | `name like '%da'` (ends with) |
| `'name\|%%' => 'd'` | `name like '%d%'` (contains; `%_%` works too) |
| `'name\|_' => 'A_a'` | `name like 'A_a'` (the value is the pattern) |
| `'id' => [1, 2]` | `id in (1, 2)` (array values) |
| `'id\|in' => [1, 2]` | `id in (1, 2)` |
| `'deleted_at\|null' => true` | `deleted_at is null` (a false value skips the condition) |
| `'created_at\|between' => [$from, $to]` | `created_at between $from and $to` (also `><`) |
| `'price\|column\|>' => 'cost'` | `price > cost` (compare two columns) |
| `'title,body\|full-text' => 'laravel'` | full text search over one or more columns |

Methods can also be written in full, in kebab case: `where-null`, `where-not-in`, `where-between-columns`...

### Negation and `or`

Add `!` (or `not`) to negate any condition, and `or` to join it with `or` instead of `and`:

```php
[
    'status|!' => 'banned',          // status <> 'banned'
    'id|in|!' => [1, 2],             // id not in (1, 2)
    'deleted_at|null|!' => true,     // deleted_at is not null
    'name' => 'Ada',
    'last_name|or' => 'Lovelace',    // ... or last_name = 'Lovelace'
]
```

### Nested groups

Use any key with the `nest` argument to wrap conditions in parentheses (the part before `|` is only a label):

```php
[
    'active' => true,
    'names|nest' => [                 // and (name = 'Ada' or name = 'Alan')
        'name' => 'Ada',
        'name|or' => 'Alan',
    ],
]
```

`names|nest|or` joins the group with `or`, and `names|nest|!` negates it (`and not (...)`).

### Casting values

`cast` runs the value through the model's casts and mutators before querying, and `cast:other_column` uses the casts of another column.
This is useful because query string values are always strings:

```php
// with protected $casts = ['is_admin' => 'boolean']
['is_admin|cast' => '0']   // is_admin = false
```

### Column names that look like arguments

Wrap the column in curly brackets when it collides with an argument name: `'{in}|!' => 'x'`.

## Joining relationships

A key that names a relationship joins it. Its value holds the conditions for the related model,
and those can contain more joins:

```php
Person::search([
    'tasks' => [                                // inner join tasks on tasks.owner_id = persons.id
        'complete' => true,
        'comments' => ['likes|>' => 5],         // inner join comments on ...
    ],
]);
```

| Key | Meaning |
| --- | --- |
| `'tasks' => [...]` | inner join (the method name, or its kebab-case form: `'administrative-division'`) |
| `'tasks\|left-join' => [...]` | join type: `join`, `inner-join`, `left-join`, `right-join`, `cross-join` |
| `'tasks@t' => [...]` | join with a table alias (needed to join the same table twice) |
| `'owner:post' => [...]` | morph-to relationships need the related type: a morph map alias or a model class |
| `'tasks' => []` | join without conditions |

Supported relationships: belongs-to, has-one, has-many, belongs-to-many, morph-to, morph-one, morph-many,
morph-to-many and morphed-by-many. "Through" relationships can't be joined.

## Options: select, sort, group and paginate

The second argument holds the options:

```php
Person::search($conditions, [
    'select' => ['persons.id', 'persons.name'],   // default: "persons.*"
    'distinct' => true,
    'order' => 'name,desc',                        // or ['name,desc', 'id'] or ['name' => 'desc']
    'group' => 'sex,country_id',                    // or ['sex', 'country_id']
    'max' => 20,                                    // results per page
    'page' => 3,
]);
```

Sort and group columns belong to the model the search starts from, and they're checked like any other column (see [Security](#security)).
`count()` takes `distinct` and `group` into account.

## Security

Search conditions usually come straight from a query string, so every column and relationship is checked before it reaches the query builder:

* **Relationships:** a key is only joined when it names a method that declares a `Relation` return type (`public function tasks(): HasMany`).
  Other model methods (`save`, `delete`, your own methods...) are **never called**.
* **Columns:** names must be plain identifiers (`column`, `table.column` or `column->json->path`).
  Columns listed in the model's `$hidden` property can't be searched or sorted.
  A `table.column` prefix must be the model's own table or alias.
* **Arguments:** unknown arguments (`name|anything`) are rejected.
* **Morph types:** `relation:type` only accepts morph map aliases and model classes.

For public endpoints, also declare explicit allow lists on the model with the `EloquentSearch` trait.
Once a list is declared, anything outside it is rejected:

```php
class Person extends Model
{
    use EloquentSearch;

    protected array $searchable = ['name', 'last_name', 'created_at'];   // columns usable in conditions
    protected array $sortable = ['name', 'created_at'];                  // order/group columns (default: $searchable)
    protected array $searchableRelations = ['tasks', 'addresses'];       // joinable relationships
}
```

Models without the trait can implement the `searchableFields()`, `sortableFields()` and `searchableRelations()` methods directly.

**Strict mode** (`SEARCHES_STRICT=true`, or `searches.strict` in the config) goes further:
models without allow lists can't be searched at all.

## HTTP endpoints

`SearchRequest` is a form request. Its input holds the conditions, plus five reserved keys:

| Key | Example | |
| --- | --- | --- |
| `page` | `page=2` | page number (needs `max`) |
| `max` | `max=20` | results per page, at most `searches.request.max_per_page` (100) |
| `order` | `order=name,desc` or `order[]=name,desc&order[]=id` | |
| `group` | `group=sex,country_id` | |
| `distinct` | `distinct=1` | |

`SearchAction` runs the search and builds the response:

```php
use SlothDevGuy\Searches\Http\Actions\SearchAction;

Route::get('people', fn (SearchAction $action) => $action->handle(Person::class));
```

```
GET /people?name%7C_%25=Ad&tasks[complete]=1&order=name,desc&max=20&page=1
```

```json
{ "items": [{ "id": 1, "name": "Ada" }], "page": 1, "pages": 1, "max": 20, "total": 1 }
```

> `|` and `%` must be URL encoded (`%7C` and `%25`). `http_build_query()` and most HTTP clients do this for you.

Without `max` and `page` the response is the plain list of items.
The pagination keys can be renamed in `searches.responses.pagination_keys`.

To add authorization, rules or route parameters, extend the request:

```php
use SlothDevGuy\Searches\Http\Requests\SearchRequest;

class CountryLocationsRequest extends SearchRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Location::class);
    }

    // GET /countries/{country}/locations filters by ['country_id' => {country}]
    protected function routeParameterKeys(): array
    {
        return ['country' => 'country_id'];
    }
}

Route::get('countries/{country}/locations', fn (CountryLocationsRequest $request) => (new SearchAction($request))->handle(Location::class));
```

## Errors

Invalid conditions throw `SlothDevGuy\Searches\Exceptions\InvalidSearchCondition`.
It implements the `SearchException` interface, and `key()` returns the condition key that failed.
Unknown arguments, forbidden columns, bad values and invalid sort directions all throw it.
The messages are safe to show to API clients.

`SearchAction` turns these exceptions into a `ValidationException`, so endpoints answer with a 422 response:

```json
{ "message": "The field [password] can not be used in searches.", "errors": { "password|_%": ["..."] } }
```

## Configuration

| Key | Default | |
| --- | --- | --- |
| `strict` | `false` (`SEARCHES_STRICT`) | require allow lists on every searched model |
| `request.max_per_page` | `100` | upper limit of `max` in `SearchRequest` |
| `full_text.min_length` | `3` | shorter full text values are ignored |
| `morphs.build_default_aliases` | `false` (`SEARCH_BUILD_DEFAULT_MORPH_ALIASES`) | use `kebab-case` class names for models missing from the morph map |
| `responses.pagination_keys` | `items`, `page`, `pages`, `max`, `total` | keys of paginated responses |
| `wheres` | built-in where classes | where builders, in the order they're tried |
| `join_types` | `join`, `inner-join`, `left-join`, `right-join`, `cross-join` | join arguments and their builder methods |
| `relationships` | built-in adapters | join adapter for each relationship class (kebab case) |

## Extending

* **Where clauses:** extend `SlothDevGuy\Searches\Where\BaseWhere`, list the query builder methods in
  `$supportedMethods`, implement `where()` and `negateIfRequired()`, and add the class to `searches.wheres`.
* **Relationships:** extend `SlothDevGuy\Searches\Join\JoinRelationship`, implement `on()` and
  `instanceOf()`, and map it in `searches.relationships` (key: the relationship class in kebab case).
* **Responses:** extend `SearchAction` and override `$defaultResponseClass` / `$defaultResponseItemClass`,
  or implement the `SearchResponseSchemaInterface` / `ItemResponseSchemaInterface` contracts.

## Upgrading

See [CHANGELOG.md](CHANGELOG.md) for the breaking changes of each release.

## License

MIT, see [LICENSE.md](LICENSE.md).
