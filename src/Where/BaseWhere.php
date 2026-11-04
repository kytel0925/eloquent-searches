<?php

namespace SlothDevGuy\Searches\Where;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use SlothDevGuy\Searches\WhereBuilder;

abstract class BaseWhere implements WhereBuilder
{
    /**
     * The query builder methods handled by this class.
     *
     * @var array<int, string>
     */
    protected static array $supportedMethods = [];

    public function __construct(
        protected SearchWhereBuilder $search,
    ) {}

    public function redirect(): WhereBuilder
    {
        return $this;
    }

    public function searchBuilder(): SearchWhereBuilder
    {
        return $this->search;
    }

    public function from(): Model
    {
        return $this->searchBuilder()->searcher()->from();
    }

    public function getQualifiedField(string $field): string
    {
        return $this->searchBuilder()->searcher()->getFromQualifiedField($field);
    }

    public function columns(): array
    {
        return (array) $this->arguments()->field();
    }

    public function arguments(): WhereArguments
    {
        return $this->searchBuilder()->arguments();
    }

    public function option(string $key, mixed $default = null): mixed
    {
        return $this->arguments()->option($key, $default);
    }

    public function pushInBuilder(Builder $builder): Builder
    {
        $where = $this->where();

        // a null method means the condition must be skipped (for example an empty full text search)
        if (! $where['method']) {
            return $builder;
        }

        $builder->{$where['method']}(...array_values($where['arguments'] ?? []));

        return $builder;
    }

    /**
     * Switch between the "and" and "or" variant of the method according to the operand.
     */
    protected function syncMethodWithOperand(): static
    {
        $arguments = $this->arguments();
        $method = $arguments->method();

        if ($arguments->operand() === 'or' && ! Str::startsWith($method, 'or')) {
            $arguments->method('or'.ucfirst($method));
        } elseif ($arguments->operand() === 'and' && Str::startsWith($method, 'or')) {
            $arguments->method(lcfirst(substr($method, 2)));
        }

        return $this;
    }

    /**
     * @return array{method: null, reason: string}
     */
    protected static function skip(string $reason): array
    {
        return ['method' => null, 'reason' => $reason];
    }

    abstract protected function negateIfRequired(): static;

    public static function supportsMethod(string $method): bool
    {
        return in_array($method, static::$supportedMethods, true);
    }
}
