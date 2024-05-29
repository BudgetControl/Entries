<?php

namespace Budgetcontrol\Entry\Domain\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payees extends Model
{
    use SoftDeletes;

    // Define the table associated with the model
    protected $table = 'payees';

    // Define the primary key column name
    protected $primaryKey = 'id';

}