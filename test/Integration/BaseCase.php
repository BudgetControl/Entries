<?php

namespace Budgetcontrol\Test\Integration;

use Carbon\Carbon;

class BaseCase extends \PHPUnit\Framework\TestCase
{

    public static function setUpBeforeClass(): void
    {
        // Configura il reporting degli errori prima di eseguire i test
        error_reporting(E_ALL);
        ini_set('display_errors', '1');
    }
    
    /**
     * build model request
     * @param float $amount
     * @param DateTime $dateTime
     * 
     * @return array
     */
    protected function makeRequest(float $amount, ?Carbon $dateTime = null): array
    {
        if (is_null($dateTime)) {
            $dateTime = Carbon::now();
        }
        
        $request = [
            "amount" => $amount,
            "note" => "test",
            "category_id" => 12,
            "account_id" => 1,
            "currency_id" => 1,
            "payment_type_id" => 1,
            "date_time" => $dateTime->format('Y-m-d H:i:s'),
            "label" => [],
            "waranty" => 0,
            "confirmed" => 1
        ];

        return $request;
    }

    /**
     * Removes the specified properties from the given data array.
     *
     * @param array $data The data array from which to remove the properties.
     * @param array $properties The properties to be removed from the data array.
     * @return array
     */
    protected function removeProperty(array &$data, $properties) {
        if (is_array($data)) {
            foreach ($data as &$value) {
                if (is_array($value)) {
                    $this->removeProperty($value, $properties);
                }
            }
            foreach ($properties as $property) {
                unset($data[$property]);
            }
        }
    }
}
