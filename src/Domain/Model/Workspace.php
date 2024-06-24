<?php
namespace Budgetcontrol\Entry\Domain\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workspace extends Model implements EntryInterface
{
    use SoftDeletes, HasFactory;
    
    protected $table = 'workspaces';

    protected $fillable = [
        'name',
        'description',
        'user_id',
        'uuid',
    ];
    
}