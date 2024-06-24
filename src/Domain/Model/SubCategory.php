<?php
namespace Budgetcontrol\Entry\Domain\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubCategory extends Model implements EntryInterface
{
    use SoftDeletes;
    
    protected $table = 'sub_categories';

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}