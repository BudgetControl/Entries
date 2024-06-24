<?php

use Budgetcontrol\Entry\Domain\Enum\EntryType;
use Illuminate\Support\Carbon;
use Phinx\Seed\AbstractSeed;

class IncomeSeeds extends AbstractSeed
{

    public function run() : void
    {
        $incomes = [
            [
                "amount" => rand(1, 1000),
                "note" => "test",
                "category_id" => 12,
                "account_id" => 1,
                "currency_id" => 1,
                "payment_type" => 1,
                "date_time" => Carbon::now()->format('Y-m-d H:i:s'),
                "label" => [],
                "waranty" => 1,
                "confirmed" => 1,
                'type' => EntryType::incoming->value,
                'workspace_id' => 1,
                'account_id' => 1,
            ],
            [
                "amount" => rand(1, 1000),
                "note" => "test",
                "category_id" => 12,
                "account_id" => 1,
                "currency_id" => 1,
                "payment_type" => 1,
                "date_time" => Carbon::now()->modify("+20 days")->format('Y-m-d H:i:s'),
                "label" => [],
                "waranty" => 1,
                "confirmed" => 1,
                'type' => EntryType::incoming->value,
                'workspace_id' => 1,
                'account_id' => 1,
            ],

            [
                "amount" => rand(1, 1000),
                "note" => "test",
                "category_id" => 12,
                "account_id" => 1,
                "currency_id" => 1,
                "payment_type" => 1,
                "date_time" => Carbon::now()->format('Y-m-d H:i:s'),
                "label" => [],
                "waranty" => 1,
                "confirmed" => 1,
                'uuid' => 'f7b3b3b0-0b7b-11ec-82a8-delete',
                'type' => EntryType::incoming->value,
                'workspace_id' => 1,
                'account_id' => 1,
            ],

            [
                "amount" => rand(1, 1000),
                "note" => "test",
                "category_id" => 12,
                "account_id" => 1,
                "currency_id" => 1,
                "payment_type" => 1,
                "date_time" => Carbon::now()->format('Y-m-d H:i:s'),
                "label" => [],
                "waranty" => 1,
                "confirmed" => 1,
                'uuid' => 'f7b3b3b0-0b7b-11ec-82a8-0242ac130003',
                'type' => EntryType::incoming->value,
                'workspace_id' => 1,
                'account_id' => 1,
            ],
            [
                "amount" => rand(1, 1000),
                "note" => "test",
                "category_id" => 12,
                "account_id" => 1,
                "currency_id" => 1,
                "payment_type" => 1,
                "date_time" => Carbon::now()->format('Y-m-d H:i:s'),
                "label" => [],
                "waranty" => 1,
                "confirmed" => 1,
                'uuid' =>  'd373d245-512d-4bff-b414-9d59781be3ee',
                'type' => EntryType::incoming->value,
                'workspace_id' => 1,
                'account_id' => 1,
            ],
        ];

        foreach ($incomes as $income) {
            \Budgetcontrol\Entry\Domain\Model\Income::create($income);
        }
    }
}
