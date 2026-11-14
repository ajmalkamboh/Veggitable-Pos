<?php

namespace App\Core\Localization;

use Illuminate\Database\Eloquent\Builder;

trait BilingualSearchable
{
    /**
     * Scope query to search across English and Urdu text columns.
     */
    public function scopeSearchBilingual(Builder $query, string $term, array $columns = ['name', 'name_ur']): Builder
    {
        $term = trim($term);
        if (empty($term)) {
            return $query;
        }

        return $query->where(function (Builder $sub) use ($term, $columns) {
            foreach ($columns as $index => $column) {
                if ($index === 0) {
                    $sub->where($column, 'like', "%{$term}%");
                } else {
                    $sub->orWhere($column, 'like', "%{$term}%");
                }
            }
        });
    }
}
