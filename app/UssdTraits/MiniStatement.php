<?php

namespace App\UssdTraits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

trait MiniStatement {

    public function StatementAccounts() {
        $Accounts = explode(",", Cache::get($this->msisdn . 'ACCOUNTS'));
        $message = "Select Bank:\n";

        $num = 1;
        foreach ($Accounts as $Account) {
            if ($Account != "") {
                $message .= $num . ". POC/2345678\n";
                $num++;
            }
        }

        $response = (object) array('id' => 'StatementAccounts', 'action' => 'con', 'response' => $message, 'map' => array((object) array('menu' => 'AccountStatement')), 'type' => 'form');

        return $response;
    }

    public function AccountStatement() {

        $response = "";
        if ($responseData[1] == "OK") {
            $Statement = 'CR Airtime 1000\nDR Mpesa 2000\nCR Salary 100000';
            $response = (object) array('id' => 'AccountSummary', 'action' => 'con', 'response' => $Statement . ' ' . PHP_EOL . '00. Home' . PHP_EOL . '000. Exit', 'map' => array((object) array('menu' => 'AccountSummary')), 'type' => 'static');
        } else {
            $response = (object) array('id' => 'AccountSummary', 'action' => 'end', 'response' => 'There was a problem processing your request', 'map' => array((object) array('menu' => 'AccountSummary')), 'type' => 'static');
        }
        return $response;
    }

}
