<?php

use Budgetcontrol\ApplicationTests\Seeds\SeedInterface;
use Phinx\Seed\AbstractSeed;

class WorkspaceSeed extends AbstractSeed implements SeedInterface
{

    public function run(): void
    {
        $wsid = 'feb89b18-07fa-4b3c-8b9f-0add708170ae';
        
        \Budgetcontrol\Library\Model\Workspace::create([
            'name' => 'test',
            'description' => 'test',
            'current' => 1,
            'user_id' => 1,
            'uuid' => $wsid,
        ]);

        // set relation with user and workspace
        $user = \Budgetcontrol\Library\Model\User::find(1);
        $workspace = \Budgetcontrol\Library\Model\Workspace::where('uuid', $wsid)->first();
    
        $workspace->users()->attach($user);
        $workspace->save();

    }

    public function getName(): string
    {
        return __CLASS__;
    }

    public function getDescription(): string
    {
        return 'Main seeds for the application, including entries, transfers, models, and savings.';
    }

    public function shouldRun(): bool
    {
        return true;
    }
}
