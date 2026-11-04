<?php

namespace SlothDevGuy\Searches\Where;

/**
 * ['deleted_at|null' => true] adds "deleted_at is null"; a false value skips the condition.
 */
class WhereNull extends BaseWhere
{
    use NegatedWhereMethods;

    protected static array $supportedMethods = ['whereNull', 'whereNotNull', 'orWhereNull', 'orWhereNotNull'];

    /**
     * @var array<string, string>
     */
    protected static array $negatedMethods = [
        'whereNull' => 'whereNotNull',
        'whereNotNull' => 'whereNull',
        'orWhereNull' => 'orWhereNotNull',
        'orWhereNotNull' => 'orWhereNull',
    ];

    public function where(): array
    {
        $arguments = $this->arguments();

        $this->negateIfRequired();
        $this->syncMethodWithOperand();

        $field = $this->getQualifiedField($arguments->field());

        if (! filter_var($arguments->value(), FILTER_VALIDATE_BOOLEAN)) {
            return static::skip('empty-value');
        }

        return [
            'method' => $arguments->method(),
            'arguments' => compact('field'),
        ];
    }
}
