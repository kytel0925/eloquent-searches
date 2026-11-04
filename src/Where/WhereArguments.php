<?php

namespace SlothDevGuy\Searches\Where;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use SlothDevGuy\Searches\BuilderOptions;
use SlothDevGuy\Searches\Exceptions\InvalidSearchCondition;

/**
 * Unpacks a "field|argument|argument" key.
 *
 * Arguments can be: a negation (!, not), an operator (=, <>, >, <, >=, <=), a like wildcard
 * (_%, %_, %%, %_%), a where method (where-null) or one of its aliases (null, in, between...),
 * the "or" operand, or one of the $customOptions ("cast", "cast:field", "nest").
 *
 * @implements Arrayable<string, mixed>
 */
class WhereArguments implements Arrayable
{
    use BuilderOptions;

    /**
     * @var array<string, mixed>
     */
    protected static array $argumentsList = [
        'not' => ['!', 'not'],
        'string_search' => ['_%', '%_', '%%', '%_%', '_'],
        'operators' => ['=', '<>', '>', '<', '>=', '<='],
        'methodPattern' => '/^where([a-z\-]*)$/',
        'method_aliases' => [
            'whereIn' => ['in'],
            'whereNull' => ['null'],
            'whereBetween' => ['><', 'between'],
            'whereColumn' => ['column'],
            'whereFullText' => ['full-text'],
        ],
    ];

    /**
     * Free form options that can be set from a key, as "option" (true) or "option:arg_1,arg_2".
     *
     * @var array<int, string>
     */
    protected static array $customOptions = ['cast', 'nest'];

    /**
     * Every argument found in the key (after the field).
     *
     * @var Collection<int, string>
     */
    protected Collection $arguments;

    /**
     * @param  array<string, mixed>  $options
     *
     * @throws InvalidSearchCondition
     */
    public function __construct(
        protected string $fieldWithArguments,
        protected mixed $value,
        array $options = [],
    ) {
        $this->options = static::defaultOptions($options);

        $this->unpackArguments();
    }

    public function method(?string $method = null): string
    {
        $this->setOptionIfNotNull('method', $method);

        return $this->options['method'];
    }

    /**
     * Get (or set) the field; comma separated fields (full text searches) are returned as a list.
     *
     * @return string|array<int, string>
     */
    public function field(?string $field = null): string|array
    {
        $this->setOptionIfNotNull('field', $field);

        $fields = array_map('trim', explode(',', (string) $this->options['field']));

        return count($fields) === 1 ? $fields[0] : $fields;
    }

    public function operator(?string $operator = null): string
    {
        $this->setOptionIfNotNull('operator', $operator);

        return $this->options['operator'];
    }

    /**
     * Get the value, or set it when an argument is given.
     */
    public function value(mixed ...$value): mixed
    {
        if (count($value) > 0) {
            $this->value = $value[0];
        }

        return $this->value;
    }

    public function operand(?string $operand = null): string
    {
        $this->setOptionIfNotNull('operand', $operand);

        return $this->options['operand'];
    }

    public function not(?bool $not = null): bool
    {
        $this->setOptionIfNotNull('not', $not);

        return $this->options['not'];
    }

    protected function setOptionIfNotNull(string $key, mixed $value): static
    {
        if (! is_null($value)) {
            $this->options[$key] = $value;
        }

        return $this;
    }

    /**
     * @throws InvalidSearchCondition
     */
    public function unpackArguments(): static
    {
        $arguments = collect(explode('|', $this->fieldWithArguments))->map(fn (string $argument) => trim($argument));

        // the field is the argument between curly brackets, or the first one
        $field = $arguments->search(fn (string $argument) => (bool) preg_match('/^\{(.*)}$/', $argument));

        if ($field !== false) {
            $field = substr($arguments->pull($field), 1, -1);
        } else {
            $field = $arguments->shift();
        }

        $this->options['field'] = (string) $field;

        $this->arguments = $arguments->values()->each(fn (string $argument) => $this->unpackArgument($argument));

        return $this;
    }

    /**
     * @throws InvalidSearchCondition
     */
    protected function unpackArgument(string $argument): void
    {
        if (in_array($argument, static::$argumentsList['not'], true)) {
            $this->options['not'] = true;
        } elseif (in_array($argument, static::$argumentsList['operators'], true)) {
            $this->options['operator'] = $argument;
        } elseif (in_array($argument, static::$argumentsList['string_search'], true)) {
            $this->options['operator'] = 'like';
            $this->options['wildcard'] = $argument === '%%' ? '%_%' : $argument;
        } elseif (preg_match(static::$argumentsList['methodPattern'], $argument)) {
            $this->options['method'] = Str::camel($argument);
        } elseif ($argument === 'or') {
            $this->options['operand'] = 'or';
        } elseif ($method = $this->getMethodFromAliases($argument)) {
            $this->options['method'] = $method;
        } else {
            $this->unpackCustomOption($argument);
        }
    }

    /**
     * @throws InvalidSearchCondition
     */
    protected function unpackCustomOption(string $argument): void
    {
        [$key, $values] = array_pad(explode(':', $argument, 2), 2, null);

        if (! in_array($key, static::$customOptions, true)) {
            throw InvalidSearchCondition::unknownArgument($argument);
        }

        $this->options[$key] = is_null($values)
            ? true
            : array_values(array_filter(array_map('trim', explode(',', $values)), fn ($value) => $value !== ''));
    }

    protected function getMethodFromAliases(string $argument): ?string
    {
        foreach (static::$argumentsList['method_aliases'] as $method => $aliases) {
            if (in_array($argument, $aliases, true)) {
                return $method;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge($this->options, [
            'value' => $this->value(),
            'field_with_arguments' => $this->fieldWithArguments,
        ]);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public static function defaultOptions(array $options = []): array
    {
        return array_merge([
            'method' => 'where',
            'field' => null,
            'operator' => '=',
            'operand' => 'and',
            'not' => false,
        ], $options);
    }
}
