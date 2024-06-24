<?php
namespace Budgetcontrol\Entry\Domain\Enum;

enum EntryType: string {
    case expenses    = 'expenses';
    case incoming   = 'incoming';
    case transfer   = 'transfer';
    case debit      = 'debit';
}