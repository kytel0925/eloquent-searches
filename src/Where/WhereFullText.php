<?php

namespace SlothDevGuy\Searches\Where;

use SlothDevGuy\Searches\Exceptions\InvalidSearchCondition;

/**
 * ['title,body|full-text' => 'laravel'] adds a full text condition over one or more columns.
 *
 * Values shorter than "searches.full_text.min_length" are ignored.
 */
class WhereFullText extends BaseWhere
{
    protected static array $supportedMethods = ['whereFullText', 'orWhereFullText'];

    /**
     * @var array<string, string>
     */
    protected static array $negatedMethods = [
        'whereFullText' => 'whereNot',
        'orWhereFullText' => 'orWhereNot',
    ];

    protected ?string $negatedMethod = null;

    protected function negateIfRequired(): static
    {
        $arguments = $this->arguments();

        if ($arguments->not()) {
            $this->negatedMethod = static::$negatedMethods[$arguments->method()] ?? null;
        }

        return $this;
    }

    public function where(): array
    {
        $arguments = $this->arguments();

        $this->syncMethodWithOperand();
        $this->negateIfRequired();

        $columns = array_map(fn (string $field) => $this->getQualifiedField($field), $this->columns());
        $value = $arguments->value();

        if (! is_scalar($value) && ! is_null($value)) {
            throw InvalidSearchCondition::invalidValue('full text searches expect a single value');
        }

        if (mb_strlen((string) $value) < (int) config('searches.full_text.min_length', 3)) {
            return static::skip('value-too-short');
        }

        $method = $arguments->method();

        if ($this->negatedMethod) {
            return [
                'method' => $this->negatedMethod,
                'arguments' => [fn ($query) => $query->whereFullText($columns, $value)],
            ];
        }

        return [
            'method' => $method,
            'arguments' => compact('columns', 'value'),
        ];
    }
}
