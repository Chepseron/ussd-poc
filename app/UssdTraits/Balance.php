<?php

namespace App\UssdTraits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

trait Balance {

    public function BalanceAccounts() {
        $Accounts = explode(",", Cache::get($this->msisdn . 'ACCOUNTS'));
        $message = "Select bank:\n";

        $num = 1;
        foreach ($Accounts as $Account) {
            if ($Account != "") {
                $message .= $num . ". POC/2345678\n";
                $num++;
            }
        }
        $response = (object) array('id' => 'BalanceAccounts', 'action' => 'con', 'response' => $message, 'map' => array((object) array('menu' => 'AccountBalance')), 'type' => 'form');
        return $response;
    }

    public function AccountBalance() {
        $response = (object) array('id' => 'AccountBalance', 'action' => 'end', 'response' => 'Available Balance: KES 1000\nActual Balance: KES 1000', 'map' => array((object) array('menu' => 'BalanceAccounts')), 'type' => 'static');
        return $response;
    }

}
