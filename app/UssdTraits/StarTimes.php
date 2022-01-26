<?php

namespace App\UssdTraits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

trait StarTimes{
	public function STVAccounts(){
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

            $response = (object) array('id' => 'STVAccounts', 'action' => 'con', 'response' => $message, 'map' => array( (object) array('menu' => 'startimesConfirm')), 'type' => 'form');
        }else{
            $response = (object) array('action' => 'con', 'response' => 'You dont have any accounts', 'map' => array( (object) array('menu' => 'home')), 'type' => 'form');
        }

        return $response;

    }
	public function startimesValidate(){
		
		$CustomerID = Cache::get($this->msisdn.'CUSTOMERID');
		$utilityAccount = Cache::get($this->msisdn.'Startimes');
	
		$DataToSend = 'FORMID:M-:MERCHANTID:007001015:ACCOUNTID:'. $utilityAccount .':INFOFIELD9:'.$this->msisdn.':CUSTOMERID:'. $CustomerID .':MOBILENUMBER:'.$this->msisdn.':BANKNAME:'.$this->BankName.':SHORTCODE:'.$this->shortcode.':BANKID:'. $this->DefaultBankID .':COUNTRY:'. $this->Country .':ACTION:GETNAME:UNIQUEID:'.$this->guid().':TRXSOURCE:USSD:';
		$this->logErr($this->msisdn, "STARTIMES VALIDATE REQUEST : ". $DataToSend);
		$ElmaResponse = $this->ElmaU($DataToSend);
		$responseData = explode(':', strip_tags($ElmaResponse));
		$this->logErr($this->msisdn, "STARTIMES VALIDATE RESPONSE : ". strip_tags($ElmaResponse));
		
		if($responseData[1] == "000" || $responseData[1] == "OK"){
			$Data = explode('|',$responseData[3]);
			Cache::add($this->msisdn.'CUSTOMERNAME', $Data[0], 2);
			$response = (object) array('id' => 'startimesValidate', 'action' => 'con', 'response' => "Name: ".$Data[0]."\n1.Accept\n2.Back", 'map' => array( (object) array('menu' => 'StarTimesformAmount')), 'type' => 'form');
		}else{
			$response = (object) array('id' => 'Startimes', 'action' => 'con', 'response' => "Invalid account: Enter your smart card number or account number:\n00.Back\n0.Main Menu", 'map' => array( (object) array('menu' => 'startimesValidate')), 'type' => 'form');
		}
		
		return $response;
		
	}
	
	public function startimesConfirm(){
		$Accounts = explode(',',Cache::get($this->msisdn.'ACCOUNTS'));
		$Selected = Cache::has($this->msisdn.'STVAccounts') ? Cache::get($this->msisdn.'STVAccounts') : "";
		$Index = $Selected - 1;
        $Account = $Accounts[$Index];
		
		$utilityAccount = Cache::get($this->msisdn.'Startimes');
		$Amount = Cache::get($this->msisdn.'StarTimesformAmount');
		$CustomerName = Cache::get($this->msisdn.'CUSTOMERNAME');

		$message = "STARTIMES\nPay ".$Amount." amount to account ".$utilityAccount."-".$CustomerName."\n Reply with:\n1. Accept\n2. Cancel";

		$response = (object) array('id' => 'startimesConfirm', 'action' => 'con', 'response' => $message, 'map' => array( (object) array('menu' => 'startimesPinConfirm')), 'type' => 'form');

		return $response;
    }
	
	public function startimesPinConfirm(){
		if(Cache::has($this->msisdn.'startimesConfirm') && Cache::get($this->msisdn.'startimesConfirm') == "1"){
			if(Cache::has($this->msisdn.'startimesPinConfirm') && Cache::get($this->msisdn.'startimesPinConfirm') != ""){
				$pin = Cache::get($this->msisdn.'startimesPinConfirm');
				$utilityAccount = Cache::get($this->msisdn.'Startimes');
				$Selected = Cache::get($this->msisdn.'STVAccounts');
				$Accounts = explode(',',Cache::get($this->msisdn.'ACCOUNTS'));
				$CustomerID = Cache::get($this->msisdn.'CUSTOMERID');
				$Index = $Selected - 1;
				$Account = $Accounts[$Index];
				$Amount = Cache::get($this->msisdn.'StarTimesformAmount');
				
				$DataToSend = 'FORMID:M-:MERCHANTID:007001015:BANKACCOUNTID:'.$Account.':INFOFIELD9:'.$this->msisdn.':ACCOUNTID:'. $utilityAccount .':CUSTOMERID:'. $CustomerID .':MOBILENUMBER:'.$this->msisdn.':BANKNAME:'.$this->BankName.':SHORTCODE:'.$this->shortcode.':BANKID:'. $this->DefaultBankID .':COUNTRY:'. $this->Country .':AMOUNT:'.$Amount.':TMPIN:'.$pin.':ACTION:PAYBILL:QUICKPAY:NO:UNIQUEID:'.$this->guid().':TRXSOURCE:USSD:';
				$this->logErr($this->msisdn, "STARTIMES REQUEST : ". $DataToSend);
				$ElmaResponse = $this->ElmaU($DataToSend);
				$responseData = explode(':', strip_tags($ElmaResponse));
				$this->logErr($this->msisdn, "STARTIMES RESPONSE : ". strip_tags($ElmaResponse));
				
				if(count($responseData) < 2){
					$response = (object) array('id' => 'startimesPinConfirm', 'action' => 'end', 'response' => "There was a problem processing your request. Please try again later:\n00.Back\n0.Main Menu", 'map' => array( (object) array('menu' => 'startimesPinConfirm')), 'type' => 'static');
				}else{
					if($responseData[1] == "000" || $responseData[1] == "OK"){
						$response = (object) array('id' => 'startimesValidate', 'action' => 'con', 'response' => $responseData[3], 'map' => array( (object) array('menu' => 'startimesPinConfirm')), 'type' => 'static');
					}else{
						$response = (object) array('id' => 'startimesPinConfirm', 'action' => 'end', 'response' => $responseData[3].":\n00.Back\n0.Main Menu", 'map' => array( (object) array('menu' => 'startimesPinConfirm')), 'type' => 'static');
					}
				}
				
			}else{
				$response = (object) array('id' => 'startimesPinConfirm', 'action' => 'con', 'response' => "Enter your PIN to complete transaction:", 'map' => array( (object) array('menu' => 'startimesPinConfirm')), 'type' => 'form');
			}
		}else{
			Cache::forget($this->msisdn.'utilityAmount');
			Cache::forget($this->msisdn.'utilityType');
			$response = (object) array('id' => 'startimesPinConfirm', 'action' => 'con', 'response' => "Transaction request was cancelled: \n0. Home \n000. Logout", 'map' => array( (object) array('menu' => 'startimesPinConfirm')), 'type' => 'form');
		}
		
		return $response;
		
	}

}
