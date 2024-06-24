<?php
namespace Budgetcontrol\Entry\Domain\Model;

use Budgetcontrol\Entry\Domain\Enum\EntryType;

class Transfer extends Entry
{
    protected $table = 'entries';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setAttribute('type', EntryType::transfer->value);
        $this->setAttribute('payee_id', null);
        $this->setAttribute('transfer', 1);
        $this->setAttribute('category_id', 75);
    }
}