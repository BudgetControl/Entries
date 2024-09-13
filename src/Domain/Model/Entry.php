<?php
declare(strict_types=1);

namespace Budgetcontrol\Entry\Domain\Model;

use Budgetcontrol\Library\Model\Entry as Model;

final class Entry extends Model {

    /**
     * Scope a query to include relations with label, account, category, and payee.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithRelations($query)
    {
        return $query->with('labels', 'wallet', 'subCategory.category', 'payee', 'currency', 'paymentType');
    }

}