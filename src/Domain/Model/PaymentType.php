<?php
namespace Budgetcontrol\Entry\Domain\Model;

use Illuminate\Database\Eloquent\Model;

class PaymentType extends Model implements EntryInterface
{
    protected $table = 'payment_types';
}