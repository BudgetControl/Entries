<?php
namespace Budgetcontrol\Entry\Domain\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EntryLabel extends Model implements EntryInterface
{
    use SoftDeletes;
    protected $table = 'entry_labels';
}