# Contributing

Thanks for helping! Bug reports, fixes and improvements are welcome.

## Reporting bugs and security issues

* Bugs: open an issue with the conditions you passed, the SQL you expected and what happened instead.
* Security issues: please don't open a public issue. Email sloth.dev.guy@gmail.com instead.

## Development setup

You need PHP 8.2+ with the `pdo_sqlite` extension, and Composer.

```bash
composer install
composer test
```

Docker works too:

```bash
docker compose run --rm php composer install
docker compose run --rm php composer check
```

The test suite runs on [Orchestra Testbench](https://packages.tools/testbench) with an in-memory SQLite database.
You don't need a Laravel application or a database server.

## Checks

| Command | What it does |
| --- | --- |
| `composer test` | PHPUnit |
| `composer lint` | Laravel Pint in test mode |
| `composer format` | Fix the code style with Pint |
| `composer analyse` | PHPStan with Larastan |
| `composer check` | lint, analyse and test |

CI runs the tests on PHP 8.2 to 8.4 against Laravel 11, 12 and 13, plus a lowest-dependencies job.

## Pull requests

* Branch from `master` and keep each pull request focused on one change.
* Add tests: a regression test for every bug fix, and tests for every new feature.
* Update the README and add a line under `Unreleased` in `CHANGELOG.md` when behavior changes.
* Keep the public API backwards compatible, or say clearly in the pull request that it's a breaking change.
* Search conditions are untrusted input. Any new argument, where builder or join adapter must go through
  `Support\SearchGuard` (see `AGENTS.md`), and must include a test proving that it can't be abused.

## Commit messages

```
{gitmoji} {type}({module}): {description}
```

For example `✨ feat(where): add where-like-any support` or `🐛 fix(request): parse comma separated groups`.

| Gitmoji | Type | Use for |
| --- | --- | --- |
| ✨ | `feat` | New features |
| 🐛 | `fix` | Bug fixes |
| 🚑️ | `hotfix` | Critical hotfixes |
| 💥 | `breaking` | Breaking changes |
| ⚡️ | `perf` | Performance |
| ♻️ | `refactor` | Refactoring |
| 📝 | `docs` | Documentation |
| ✅ | `test` | Tests |
| 🔧 | `config` | Configuration files |
| 👷 | `ci` | CI build system |
| 🔒️ | `security` | Security fixes |
| ⬆️ ➕ ➖ | `deps` | Upgrade, add or remove dependencies |
| 🚚 | `move` | Move or rename files |
| 🔥 | `remove` | Remove code or files |

Keep documentation updates in the same commit as the change that requires them.
