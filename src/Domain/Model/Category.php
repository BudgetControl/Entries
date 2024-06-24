<?php
namespace Budgetcontrol\Entry\Domain\Model;

use Illuminate\Database\Eloquent\Model;

class Category extends Model implements EntryInterface
{
    protected $table = 'categories';
}