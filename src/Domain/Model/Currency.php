<?php
namespace Budgetcontrol\Entry\Domain\Model;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model implements EntryInterface
{
    protected $table = 'currencies';
}