<?php

namespace App\UssdTraits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

trait UmemePostpaid {

    public function UmemePostpaidAccounts() {
        $all_accounts = Cache::get($this->msisdn . 'ACCOUNTS');
        $Accounts = explode(',', $all_accounts);

        if (count($Accounts) > 0) {
            $message = "Select Bank \n";

            $num = 1;
            foreach ($Accounts as $Account) {
                if ($Account != "") {
                    $message .= $num . ". POC/2345678\n";
                    $num++;
                }
            }
            $message .= "00. Home \n 000. Exit";

            $response = (object) array('id' => 'UmemePostpaidAccounts', 'action' => 'con', 'response' => $message, 'map' => array((object) array('menu' => 'umemePostpaidConfirm')), 'type' => 'form');
        } else {
            $response = (object) array('action' => 'con', 'response' => 'You dont have any accounts', 'map' => array((object) array('menu' => 'home')), 'type' => 'form');
        }

        return $response;
    }

    public function umemePostpaidValidate() {

        $response = (object) array('id' => 'umemePostpaidValidate', 'action' => 'con', 'response' => "Account name: POC POC\n1.Accept\n2.Back", 'map' => array((object) array('menu' => 'umemePostpaidAmount')), 'type' => 'form');

        return $response;
    }

    public function umemePostpaidConfirm() {
        $utilityAccount = Cache::get($this->msisdn . 'umemePostpaid');
        $Amount = Cache::get($this->msisdn . 'umemePostpaidAmount');
        $message = "KPLC Postpaid\nPay " . $Amount . " UGX to account " . $utilityAccount . "\n Reply with:\n1. Accept\n2. Cancel";
        $response = (object) array('id' => 'umemePostpaidConfirm', 'action' => 'con', 'response' => $message, 'map' => array((object) array('menu' => 'umemePostpaidPinConfirm')), 'type' => 'form');
        return $response;
    }

    public function umemePostpaidPinConfirm() {
        if (Cache::has($this->msisdn . 'umemePostpaidConfirm') && Cache::get($this->msisdn . 'umemePostpaidConfirm') == "1") {
            if (Cache::has($this->msisdn . 'umemePostpaidPinConfirm') && Cache::get($this->msisdn . 'umemePostpaidPinConfirm') != "") {
                $response = (object) array('id' => 'umemePostpaidPinConfirm', 'action' => 'end', 'response' => $responseData[3] . "Your bill payment was successful:\n00.Back\n0.Main Menu", 'map' => array((object) array('menu' => 'umemePostpaidPinConfirm')), 'type' => 'static');
            } else {
                $response = (object) array('id' => 'umemePostpaidPinConfirm', 'action' => 'con', 'response' => "Enter your PIN to complete transaction:", 'map' => array((object) array('menu' => 'umemePostpaidPinConfirm')), 'type' => 'form');
            }
        } else {

            $response = (object) array('id' => 'umemePostpaidPinConfirm', 'action' => 'con', 'response' => "Transaction request was cancelled: \n0. Home \n000. Logout", 'map' => array((object) array('menu' => 'umemePostpaidPinConfirm')), 'type' => 'form');
        }

        return $response;
    }

}
