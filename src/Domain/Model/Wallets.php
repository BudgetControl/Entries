<?php
namespace Budgetcontrol\Entry\Domain\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Wallets extends Model
{
    use SoftDeletes;
    
    protected $table = 'accounts';
}