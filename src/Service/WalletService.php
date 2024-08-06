<?php

namespace Budgetcontrol\Entry\Service;

use Budgetcontrol\Library\Model\Entry;
use Budgetcontrol\Library\Model\Wallet;
use Webit\Wrapper\BcMath\BcMathNumber;
use Budgetcontrol\Entry\Facade\BcMathFacade as BcMath;

class WalletService
{

    public function insert(Entry $entry)
    {

        $this->updateWalletBalance($entry);
        $entry->save();
    }

    public function update(Entry $entry)
    {
        $entryOld = Entry::find($entry->id);
        if (isset($oldEntry)) {
            if ($this->checkDifference($entry, $entryOld) === true) {
                $entry->save();
                $this->insert($entry);
            }
        } else {
            $this->insert($entry);
        }
    }

    private function checkDifference(Entry $entry, Entry $entryOld)
    {
        $isChanged = false;

        if ($entry->amount != $entryOld->amount) {
            $isChanged = true;
        }

        if ($entry->account_id != $entryOld->account_id) {
            $isChanged = true;
        }

        if ($entry->confirmed != $entryOld->confirmed) {
            $isChanged = true;
        }

        if ($entry->planned != $entryOld->planned) {
            $isChanged = true;
        }

        if($isChanged === true) {
            $entryOld->amount = $entryOld->amount * -1;
            $this->updateWalletBalance($entryOld);
        }
        
        return $isChanged;
    }

    private function updateWalletBalance(Entry $entry)
    {
        if ($entry->planned != 1 && $entry->confirmed != 0) {
            // update balance
            $wallet = Wallet::find($entry->account_id);
            $math = new BcMathNumber($wallet->balance);
            $wallet->balance = $math->add($entry->amount)->toFloat();
            $wallet->save();
        }
    }

    /**
     * Removes an entry from the wallet.
     *
     * @param Entry $entry The entry to be removed.
     * @return void
     */
    public function remove(Entry $entry)
    {
        $amount = $entry->amount * -1;
        $entry->amount = $amount;
        $this->updateWalletBalance($entry);
    }
}
