<?php

namespace App\UssdTraits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Session;
use Carbon\Carbon;

trait Airtime {

    public function AirtimeOwnAccounts() {
        $all_accounts = Cache::get($this->msisdn . 'ACCOUNTS');
        $Accounts = explode(',', $all_accounts);

        if (count($Accounts) > 0) {
            $message = "Select Source Account \n";

            $num = 1;
            foreach ($Accounts as $Account) {
                if ($Account != "") {
                    $message .= $num . ". POC/2345678\n";
                    $num++;
                }
            }
            $message .= "00. Home \n 000. Exit";

            $response = (object) array('id' => 'AirtimeOwnAccounts', 'action' => 'con', 'response' => $message, 'map' => array((object) array('menu' => 'Airtime')), 'type' => 'form');
        } else {
            $response = (object) array('action' => 'con', 'response' => 'You dont have any accounts', 'map' => array((object) array('menu' => 'home')), 'type' => 'form');
        }

        return $response;
    }

    public function AirtimeOtherAccounts() {
        $all_accounts = Cache::get($this->msisdn . 'ACCOUNTS');
        $Accounts = explode(',', $all_accounts);

        if (count($Accounts) > 0) {
            $message = "Select Source Account \n";

            $num = 1;
            foreach ($Accounts as $Account) {
                if ($Account != "") {
                    $message .= $num . ". POC/2345678\n";
                    $num++;
                }
            }
            $message .= "00. Home \n 000. Exit";

            $response = (object) array('id' => 'AirtimeOtherAccounts', 'action' => 'con', 'response' => $message, 'map' => array((object) array('menu' => 'Airtime')), 'type' => 'form');
        } else {
            $response = (object) array('action' => 'con', 'response' => 'You dont have any accounts', 'map' => array((object) array('menu' => 'home')), 'type' => 'form');
        }

        return $response;
    }

    public function Airtime() {
        $otherPhone = Cache::has($this->msisdn . 'pinlessMsisdn2') ? Cache::get($this->msisdn . 'pinlessMsisdn2') : "";
        $phone = "";
        if ($otherPhone != "") {
            $phone = "256" . substr($otherPhone, 1);
        } else {
            $phone = $this->msisdn;
        }
        $TopUpAmount = Cache::get($this->msisdn . 'PinlessAirtimeAmount');
        Cache::add($this->msisdn . 'TOPUPPHONE', $phone, 2);
        $response = "";
        $response = "You are buying airtime of " . $TopUpAmount . " from POC/2345678 bank Reply with:\n1. Accept\n2. Cancel \n0. Home";
        return (object) array('id' => 'Airtime', 'action' => 'con', 'response' => $response, 'map' => array((object) array('menu' => 'AirtimeConfirm')), 'type' => 'form');
    }

    public function AirtimeConfirm() {
        $start_time = microtime(true);
        if (Cache::has($this->msisdn . 'Airtime') && Cache::get($this->msisdn . 'Airtime') == "1") {
            $phone = Cache::has($this->msisdn . 'TOPUPPHONE') ? Cache::get($this->msisdn . 'TOPUPPHONE') : "";
            $Amount = Cache::get($this->msisdn . 'PinlessAirtimeAmount');
            $response = "You have successfully Purchased Airtime of KES " . $Amount . " To " . $phone . ", Thank you for Banking with us";
            return $response;
        } else {
            $end_time = microtime(true);
            Cache::forget($this->msisdn . 'PinlessAirtimeAmount');
            $this->logErr($this->msisdn, "TRANSACTION TIME : " . ($start_time - $end_time));
            return (object) array('id' => 'AirtimeConfirm', 'action' => 'con', 'response' => 'Transaction request was cancelled. ' . PHP_EOL . '00. Home ' . PHP_EOL . '000. Exit', 'map' => array((object) array('menu' => 'AirtimeConfirm')), 'type' => 'form');
        }
    }

}
