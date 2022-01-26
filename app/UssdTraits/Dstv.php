<?php

namespace App\UssdTraits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

trait Dstv{
	public function TVAccounts(){
        $all_accounts = Cache::get($this->msisdn.'ACCOUNTS');
        $Accounts = explode(',', $all_accounts);

        if(count($Accounts) > 0){
            $message = "Select Bank \n";

            $num = 1;
            foreach($Accounts as $Account){
                if($Account != ""){
					$message .= $num.". ".$Account."\n";
					$num++;
				}
            }
            $message .= "00. Home \n 000. Exit";

            $response = (object) array('id' => 'TVAccounts', 'action' => 'con', 'response' => $message, 'map' => array( (object) array('menu' => 'dstvConfirm')), 'type' => 'form');
        }else{
            $response = (object) array('action' => 'con', 'response' => 'You dont have any accounts', 'map' => array( (object) array('menu' => 'home')), 'type' => 'form');
        }

        return $response;

    }
	public function dstvValidate(){
		
		$CustomerID = Cache::get($this->msisdn.'CUSTOMERID');
		$utilityAccount = Cache::get($this->msisdn.'dstvUG');
		
		$utility = "";
		if(Cache::has($this->msisdn.'dstvPackage')){
			$package = Cache::get($this->msisdn.'dstvPackage');
			$prices = ["219000","129000","79000","49000","33000"];
			$Amount = $prices[$package - 1];
			$utility = "DSTV";
			$service = "007001001";
		}else if(Cache::has($this->msisdn.'gotvPackage')){
			$package = Cache::get($this->msisdn.'gotvPackage');
			$prices = ["26000","16000","11000","25000","70000","39000"];
			$Amount = $prices[$package - 1];
			$utility = "GOTV";
			$service = "007001014";
		}else if(Cache::has($this->msisdn.'dstvpvrPackage')){
			$package = Cache::get($this->msisdn.'dstvpvrPackage');
			$prices = ["41250","41250","41250"];
			$Amount = $prices[$package - 1];
			$utility = "DSTV";
			$service = "007001001";
		}
	
		$DataToSend = 'FORMID:M-:MERCHANTID:'.$service.':ACCOUNTID:'. $utilityAccount .':CUSTOMERID:'. $CustomerID .':INFOFIELD1:DSTV:INFOFIELD9:'.$this->msisdn.':MOBILENUMBER:'.$this->msisdn.':BANKNAME:'.$this->BankName.':SHORTCODE:'.$this->shortcode.':BANKID:'. $this->DefaultBankID .':COUNTRY:'. $this->Country .':ACTION:GETNAME:UNIQUEID:'.$this->guid().':TRXSOURCE:USSD:';
		$this->logErr($this->msisdn, "DSTV VALIDATE REQUEST : ". $DataToSend);
		$ElmaResponse = $this->ElmaU($DataToSend);
		$responseData = explode(':', strip_tags($ElmaResponse));
		$this->logErr($this->msisdn, "DSTV VALIDATE RESPONSE : ". strip_tags($ElmaResponse));
		
		if($responseData[1] == "000" || $responseData[1] == "OK"){
			//$Data = explode('|',$responseData[3]);
			$Data = str_replace("|", " ", $responseData[3]);
			$response = (object) array('id' => 'dstvValidate', 'action' => 'con', 'response' => $Data."\n1.Accept\n2.Back", 'map' => array( (object) array('menu' => 'TVAccounts')), 'type' => 'form');
			//$response = (object) array('id' => 'dstvValidate', 'action' => 'con', 'response' => "Name: ".$Data[0].", Current Balance:". explode(' ', $Data[1])[0].", Due Date: ".$Data[2]."\n1.Accept\n2.Back", 'map' => array( (object) array('menu' => 'TVAccounts')), 'type' => 'form');
		}else{
			$response = (object) array('id' => 'dstvUG', 'action' => 'con', 'response' => "Invalid account: Enter your smart card number or DSTV account number:\n00.Back\n0.Main Menu", 'map' => array( (object) array('menu' => 'dstvValidate')), 'type' => 'form');
		}
		
		return $response;
		
	}
	
	public function dstvConfirm(){
		$Accounts = explode(',',Cache::get($this->msisdn.'ACCOUNTS'));
		$Selected = Cache::has($this->msisdn.'TVAccounts') ? Cache::get($this->msisdn.'TVAccounts') : "";
		$Index = $Selected - 1;
        $Account = $Accounts[$Index];
		$package = "";
		
		$utility = "";
		if(Cache::has($this->msisdn.'dstvPackage')){
			$package = Cache::get($this->msisdn.'dstvPackage');
			$prices = ["219000","129000","79000","49000","33000"];
			$Amount = $prices[$package - 1];
			$utility = "DSTV";
			$service = "007001001";
		}else if(Cache::has($this->msisdn.'gotvPackage')){
			$package = Cache::get($this->msisdn.'gotvPackage');
			$prices = ["26000","16000","11000","25000","70000","39000"];
			$Amount = $prices[$package - 1];
			$utility = "GOTV";
			$service = "007001014";
		}else if(Cache::has($this->msisdn.'dstvpvrPackage')){
			$package = Cache::get($this->msisdn.'dstvpvrPackage');
			$prices = ["41250","41250","41250"];
			$Amount = $prices[$package - 1];
			$utility = "DSTV";
			$service = "007001001";
		}
		
        Cache::add($this->msisdn.'utilityAmount', $Amount, 2);
        Cache::add($this->msisdn.'utilityType', $service, 2);
		
		$utilityAccount = Cache::get($this->msisdn.'dstvUG');

		$message = $utility."\nPay ".$Amount." UGX to ".$utility." account ".$utilityAccount."\n Reply with:\n1. Accept\n2. Cancel";
		Cache::forget($this->msisdn.'dstvPinConfirm');
		$response = (object) array('id' => 'dstvConfirm', 'action' => 'con', 'response' => $message, 'map' => array( (object) array('menu' => 'dstvPinConfirm')), 'type' => 'form');

		return $response;
    }
	
	public function dstvPinConfirm(){
		if(Cache::has($this->msisdn.'dstvConfirm') && Cache::get($this->msisdn.'dstvConfirm') == "1"){
			if(Cache::has($this->msisdn.'dstvPinConfirm') && Cache::get($this->msisdn.'dstvPinConfirm') != ""){
				$pin = Cache::get($this->msisdn.'dstvPinConfirm');
				$utilityAccount = Cache::get($this->msisdn.'dstvUG');
				$Selected = Cache::get($this->msisdn.'TVAccounts');
				$Accounts = explode(',',Cache::get($this->msisdn.'ACCOUNTS'));
				$CustomerID = Cache::get($this->msisdn.'CUSTOMERID');
				$Index = $Selected - 1;
				$Account = $Accounts[$Index];
				$Amount = Cache::get($this->msisdn.'utilityAmount');
				$Type = Cache::get($this->msisdn.'utilityType');
				
				$DataToSend = 'FORMID:M-:MERCHANTID:'.$Type.':BANKACCOUNTID:'.$Account.':ACCOUNTID:'. $utilityAccount .':CUSTOMERID:'. $CustomerID .':MOBILENUMBER:'.$this->msisdn.':BANKNAME:'.$this->BankName.':SHORTCODE:'.$this->shortcode.':BANKID:'. $this->DefaultBankID .':COUNTRY:'. $this->Country .':AMOUNT:'.$Amount.':TMPIN:'.$pin.':ACTION:PAYBILL:QUICKPAY:NO:UNIQUEID:'.$this->guid().':TRXSOURCE:USSD:';
				$this->logErr($this->msisdn, "DSTV REQUEST : ". $DataToSend);
				$ElmaResponse = $this->ElmaU($DataToSend);
				$responseData = explode(':', strip_tags($ElmaResponse));
				$this->logErr($this->msisdn, "DSTV RESPONSE : ". strip_tags($ElmaResponse));
				
				if(count($responseData) < 2){
					Cache::forget($this->msisdn.'utilityAmount');
					Cache::forget($this->msisdn.'utilityType');
					$response = (object) array('id' => 'dstvPinConfirm', 'action' => 'end', 'response' => "There was a problem processing your request. Please try again later:\n00.Back\n0.Main Menu", 'map' => array( (object) array('menu' => 'dstvPinConfirm')), 'type' => 'static');
				}else{
					if($responseData[1] == "000" || $responseData[1] == "OK"){
						$response = (object) array('id' => 'dstvPinConfirm', 'action' => 'con', 'response' => $responseData[3], 'map' => array( (object) array('menu' => 'dstvPinConfirm')), 'type' => 'static');
					}else{
						$response = (object) array('id' => 'dstvPinConfirm', 'action' => 'end', 'response' => $responseData[3].":\n00.Back\n0.Main Menu", 'map' => array( (object) array('menu' => 'dstvPinConfirm')), 'type' => 'static');
					}
					Cache::forget($this->msisdn.'utilityAmount');
					Cache::forget($this->msisdn.'utilityType');
				}
				
			}else{
				$response = (object) array('id' => 'dstvPinConfirm', 'action' => 'con', 'response' => "Enter your PIN to complete transaction:", 'map' => array( (object) array('menu' => 'dstvPinConfirm')), 'type' => 'form');
			}
		}else{
			Cache::forget($this->msisdn.'utilityAmount');
			Cache::forget($this->msisdn.'utilityType');
			$response = (object) array('id' => 'dstvPinConfirm', 'action' => 'con', 'response' => "Transaction request was cancelled: \n0. Home \n000. Logout", 'map' => array( (object) array('menu' => 'dstvPinConfirm')), 'type' => 'form');
		}
		
		return $response;
		
	}

}
