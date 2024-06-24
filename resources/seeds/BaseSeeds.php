<?php

use Budgetcontrol\Entry\Domain\Model\Payee;
use Budgetcontrol\Entry\Domain\Model\Wallet;
use Budgetcontrol\Entry\Domain\Model\Workspace;
use Phinx\Seed\AbstractSeed;

class BaseSeeds extends AbstractSeed {

    public function run(): void
    {
        Wallet::create([
            'date_time' => \Illuminate\Support\Carbon::now()->format('Y-m-d H:i:s'),
            'name' => 'Wallet 1',
            'color' => '#0090ffa8',
            'type' => 'Bank',
            'currency' => 2,
            'balance' => 0,
            'workspace_id' => 1,
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
        ]);
        
        Workspace::create([
            'name' => 'Wallet 1',
            'description' => '',
            'user_id' => 1,
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
        ]);

        Payee::create([
            'name' => 'Payee 1',
            'workspace_id' => 1,
            'date_time' => \Illuminate\Support\Carbon::now()->format('Y-m-d H:i:s'),
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
        ]);
    }
}