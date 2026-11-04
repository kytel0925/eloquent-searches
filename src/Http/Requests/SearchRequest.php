<?php

namespace SlothDevGuy\Searches\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A form request whose input holds search conditions plus a few reserved options:
 *
 *  - page: the page number (needs max)
 *  - max: results per page, limited by "searches.request.max_per_page"
 *  - order: "column", "column,desc" or a list of those
 *  - group: comma separated columns or a list of columns
 *  - distinct: boolean
 *
 * Extend it to add authorization, validation rules or route parameters.
 */
class SearchRequest extends FormRequest
{
    use MergesRouteParameters;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'max' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:'.(int) config('searches.request.max_per_page', 100)],
            'distinct' => ['sometimes', 'nullable', 'boolean'],
            'order' => ['sometimes', 'nullable', 'array'],
            'order.*' => ['string'],
            'group' => ['sometimes', 'nullable', 'array'],
            'group.*' => ['string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->mergeRouteParameters();

        // "order" and "group" accept a string or a list, normalize them into lists
        $normalized = [];

        if (is_string($order = $this->input('order'))) {
            $normalized['order'] = $order === '' ? [] : [$order];
        }

        if (is_string($group = $this->input('group'))) {
            $normalized['group'] = array_values(array_filter(array_map('trim', explode(',', $group))));
        }

        $this->merge($normalized);
    }

    /**
     * Get the search conditions: every input except the reserved keys.
     *
     * @param  array<int, string>|string|null  $keys
     * @return array<string, mixed>
     */
    public function getConditions(array|string|null $keys = null): array
    {
        return collect($this->all($keys))
            ->except(static::reservedKeys())
            ->all();
    }

    /**
     * Get the search options (page, max, order, group, distinct) found in the request.
     *
     * @return array<string, mixed>
     */
    public function searchOptions(): array
    {
        return [
            'page' => $this->filled('page') ? (int) $this->input('page') : null,
            'max' => $this->filled('max') ? (int) $this->input('max') : null,
            'order' => $this->order(),
            'group' => $this->group(),
            'distinct' => $this->boolean('distinct'),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function order(): array
    {
        return array_values(array_filter((array) $this->input('order', []), 'is_string'));
    }

    /**
     * @return array<int, string>
     */
    public function group(): array
    {
        $group = $this->input('group', []);

        if (is_string($group)) {
            $group = explode(',', $group);
        }

        return array_values(array_filter(array_map('trim', array_filter((array) $group, 'is_string'))));
    }

    /**
     * Input keys that hold options instead of search conditions.
     *
     * @return array<int, string>
     */
    public static function reservedKeys(): array
    {
        return ['page', 'max', 'distinct', 'order', 'group'];
    }
}
