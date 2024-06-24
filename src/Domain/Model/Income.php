<?php
namespace Budgetcontrol\Entry\Domain\Model;

use Budgetcontrol\Entry\Domain\Enum\EntryType;

class Income extends Entry
{
    protected $table = 'entries';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setAttribute('type', EntryType::incoming->value);
        $this->setAttribute('transfer', false);
        $this->setAttribute('transfer_id', null);
        $this->setAttribute('payee_id', null);

        parent::__construct($attributes);
    }
}