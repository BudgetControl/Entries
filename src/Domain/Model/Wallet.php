<?php
namespace Budgetcontrol\Entry\Domain\Model;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Budgetcontrol\Library\Model\Wallet as ModelWallet;
use BudgetcontrolLibs\Crypt\Traits\Crypt;

final class Wallet extends ModelWallet
{
    use Crypt;
    
    protected $table = 'wallets';

    public function name(): Attribute
    {
        $this->key = env('APP_KEY');
        
        return Attribute::make(
            get: fn (string $value) => $this->decrypt($value),
            set: fn (string $value) => $this->encrypt($value),
        );
    }
}