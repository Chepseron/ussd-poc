<?php

namespace App\UssdTraits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

trait Water {

    public function WaterAccounts() {
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

            $response = (object) array('id' => 'WaterAccounts', 'action' => 'con', 'response' => $message, 'map' => array((object) array('menu' => 'waterConfirm')), 'type' => 'form');
        } else {
            $response = (object) array('action' => 'con', 'response' => 'You dont have any accounts', 'map' => array((object) array('menu' => 'home')), 'type' => 'form');
        }

        return $response;
    }

    public function waterValidate() {
        $response = (object) array('id' => 'waterValidate', 'action' => 'con', 'response' => "Name: POC POC, Current Balance:1000\n1.Accept\n2.Back", 'map' => array((object) array('menu' => 'WaterformAmount')), 'type' => 'form');
        return $response;
    }

    public function waterConfirm() {
        $Amount = Cache::get($this->msisdn . 'WaterformAmount');
        $utilityAccount = Cache::get($this->msisdn . 'NATWATERUG');
        $message = "National Water\nPay " . $Amount . " UGX to National Water account " . $utilityAccount . "\n Reply with:\n1. Accept\n2. Cancel";
        $response = (object) array('id' => 'waterConfirm', 'action' => 'con', 'response' => $message, 'map' => array((object) array('menu' => 'waterPinConfirm')), 'type' => 'form');

        return $response;
    }

    public function waterPinConfirm() {
        if (Cache::has($this->msisdn . 'waterConfirm') && Cache::get($this->msisdn . 'waterConfirm') == "1") {
            if (Cache::has($this->msisdn . 'waterPinConfirm') && Cache::get($this->msisdn . 'waterPinConfirm') != "") {
                $response = (object) array('id' => 'waterPinConfirm', 'action' => 'end', 'response' => "You have successfully paid your water bill:\n00.Back\n0.Main Menu", 'map' => array((object) array('menu' => 'waterPinConfirm')), 'type' => 'static');
            } else {
                $response = (object) array('id' => 'waterPinConfirm', 'action' => 'con', 'response' => "Enter your PIN to complete transaction:", 'map' => array((object) array('menu' => 'waterPinConfirm')), 'type' => 'form');
            }
        } else {
            $response = (object) array('id' => 'waterPinConfirm', 'action' => 'con', 'response' => "Transaction request was cancelled: \n0. Home \n000. Logout", 'map' => array((object) array('menu' => 'waterPinConfirm')), 'type' => 'form');
        }

        return $response;
    }

}
