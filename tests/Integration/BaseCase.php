<?php
namespace Budgetcontrol\Tests\Integration;

use Carbon\Carbon;

class BaseCase extends \PHPUnit\Framework\TestCase
{
    const DOMAIN = 'http://budgetcontrol-ms-entries';

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
            "payment_type" => 1,
            "date_time" => $dateTime->format('Y-m-d H:i:s'),
            "label" => [],
            "waranty" => 1,
            "confirmed" => 1
        ];

        return $request;
    }
}
