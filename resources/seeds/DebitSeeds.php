<?php

use Budgetcontrol\Entry\Domain\Enum\EntryType;
use Illuminate\Support\Carbon;
use Phinx\Seed\AbstractSeed;

class DebitSeeds extends AbstractSeed
{

    public function run() : void
    {
        $payee = [
            [
                "amount" => rand(1, 1000),
                "note" => "test",
                "category_id" => 12,
                "account_id" => 4,
                "currency_id" => 1,
                "payment_type" => 1,
                "end_date_time" => Carbon::now()->modify("+20 days")->format('Y-m-d H:i:s'),
                "date_time" => Carbon::now()->modify("+20 days")->format('Y-m-d H:i:s'),
                "label" => [],
                "waranty" => 1,
                "confirmed" => 1,
                'type' => EntryType::incoming->value,
                'workspace_id' => 1,
                'account_id' => 1,
                'payee_id' => 1,
                'uuid' => 'f7b3b3b0-0b7b-11ec-82a8-0242ac130003',
            ],
            [
                "amount" => rand(1, 1000),
                "note" => "test",
                "category_id" => 12,
                "account_id" => 4,
                "currency_id" => 1,
                "payment_type" => 1,
                "end_date_time" => Carbon::now()->modify("+20 days")->format('Y-m-d H:i:s'),
                "date_time" => Carbon::now()->format('Y-m-d H:i:s'),
                "label" => [],
                "waranty" => 1,
                "confirmed" => 1,
                'uuid' => 'f7d92908-bc1a-4336-8c2d-fb1648eacbe6',
                'type' => EntryType::incoming->value,
                'workspace_id' => 1,
                'account_id' => 1,
                'payee_id' => 1,
            ],
            [
                "amount" => rand(1, 1000),
                "note" => "test",
                "category_id" => 12,
                "account_id" => 4,
                "currency_id" => 1,
                "payment_type" => 1,
                "end_date_time" => Carbon::now()->modify("+20 days")->format('Y-m-d H:i:s'),
                "date_time" => Carbon::now()->format('Y-m-d H:i:s'),
                "label" => [],
                "waranty" => 1,
                "confirmed" => 1,
                'uuid' =>  '2b598724-4766-4bec-9529-da3196533d22',
                'type' => EntryType::incoming->value,
                'workspace_id' => 1,
                'account_id' => 1,
                'payee_id' => 1,
            ],
        ];

        foreach ($payee as $payee) {
            $payee['amount'] = $payee['amount'] * -1;
            \Budgetcontrol\Entry\Domain\Model\Debit::create($payee);
        }
    }
}
