<?php
namespace Budgetcontrol\Entry\Domain\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Wallet extends Model implements EntryInterface
{
    use SoftDeletes, HasFactory;
    
    protected $table = 'accounts';

    protected $fillable = [
        'date_time',
        'name',
        'color',
        'type',
        'currency',
        'balance',
        'workspace_id',
        'uuid',
    ];
}