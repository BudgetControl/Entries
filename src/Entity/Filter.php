<?php
declare(strict_types=1);

namespace Budgetcontrol\Entry\Entity;

final class Filter {

    protected array $filters = [];

    private const FILTERS = [
        'planned',
        'wallet_id',
        'amount',
        'date_time',
        'category_id',
        'currency_id',
        'confirmed',
        'waranty',
        'payee_id',
        'payment_type',
        'type'
    ];

    public function __construct(array $filters) {
        $this->validate($filters);

        foreach($filters as $key => $filter) {
            if(strpos($filter,'|') !== false) {
                $build = explode('|', $filter);
                $filter = [
                    'condition' => $build[0],
                    'value' => $build[1]
                ];
            } elseif(strpos($filter, ',') !== false) {
                $build = explode(',', $filter);
                $filter = [
                    'value' => $build
                ];
            } else {
                $filter = [
                    'value' => $filter
                ];
            }

            $this->filters[$key] = $filter;
        }
        
    }
    
    private function validate(array $filters) {
        foreach($filters as $key => $value) {
            if(!in_array($key, self::FILTERS)) {
                throw new \InvalidArgumentException("Invalid filter key: $key");
            }
        }
    }

    /**
     * Get the value of filters
     *
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }
}