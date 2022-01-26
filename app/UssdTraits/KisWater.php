<?php

namespace App\UssdTraits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

trait KisWater{
	public function KisWaterAccounts(){
        $all_accounts = Cache::get($this->msisdn.'ACCOUNTS');
        $Accounts = explode(',', $all_accounts);

        if(count($Accounts) > 0){
            $message = "Select Bank \n";

            $num = 1;
            foreach($Accounts as $Account){
                if($Account != ""){
					$message .= $num.". HFB/".$Account."\n";
					$num++;
				}
            }
            $message .= "00. Home \n 000. Exit";

            $response = (object) array('id' => 'KisWaterAccounts', 'action' => 'con', 'response' => $message, 'map' => array( (object) array('menu' => 'kisWaterConfirm')), 'type' => 'form');
        }else{
            $response = (object) array('action' => 'con', 'response' => 'You dont have any accounts', 'map' => array( (object) array('menu' => 'home')), 'type' => 'form');
        }

        return $response;

    }
	public function KisWaterValidate(){
		
		$CustomerID = Cache::get($this->msisdn.'CUSTOMERID');
		$utilityAccount = Cache::get($this->msisdn.'KIS');
	
		$DataToSend = 'FORMID:M-:MERCHANTID:007001020:ACCOUNTID:'. $utilityAccount .':CUSTOMERID:'. $CustomerID .':MOBILENUMBER:'.$this->msisdn.':BANKNAME:'.$this->BankName.':SHORTCODE:'.$this->shortcode.':BANKID:'. $this->DefaultBankID .':COUNTRY:'. $this->Country .':ACTION:GETNAME:UNIQUEID:'.$this->guid().':TRXSOURCE:USSD:';
		$this->logErr($this->msisdn, "KIS VALIDATE REQUEST : ". $DataToSend);
		$ElmaResponse = $this->ElmaU($DataToSend);
		$responseData = explode(':', strip_tags($ElmaResponse));
		$this->logErr($this->msisdn, "KIS VALIDATE RESPONSE : ". strip_tags($ElmaResponse));
		
		if($responseData[1] == "000" || $responseData[1] == "OK"){
			//$Data = explode('|',$responseData[3]);
			$response = (object) array('id' => 'KisWaterValidate', 'action' => 'con', 'response' => $responseData[3]."\n1.Accept\n2.Back", 'map' => array( (object) array('menu' => 'KISformAmount')), 'type' => 'form');
		}else{
			$response = (object) array('id' => 'KIS', 'action' => 'con', 'response' => "Invalid account: Enter account number:\n00.Back\n0.Main Menu", 'map' => array( (object) array('menu' => 'KisWaterValidate')), 'type' => 'form');
		}
		
		return $response;
		
	}
	
	public function kisWaterConfirm(){
		$Accounts = explode(',',Cache::get($this->msisdn.'ACCOUNTS'));
		$Selected = Cache::has($this->msisdn.'KisWaterAccounts') ? Cache::get($this->msisdn.'KisWaterAccounts') : "";
		$Index = $Selected - 1;
        $Account = $Accounts[$Index];
		
		$utilityAccount = Cache::get($this->msisdn.'KIS');
		$Amount = Cache::get($this->msisdn.'KISformAmount');

		$message = "KIS\nPay ".$Amount." UGX to account ".$utilityAccount."\n Reply with:\n1. Accept\n2. Cancel";

		$response = (object) array('id' => 'kisWaterConfirm', 'action' => 'con', 'response' => $message, 'map' => array( (object) array('menu' => 'kisWaterPinConfirm')), 'type' => 'form');

		return $response;
    }
	
	public function kisWaterPinConfirm(){
		if(Cache::has($this->msisdn.'kisWaterConfirm') && Cache::get($this->msisdn.'kisWaterConfirm') == "1"){
			if(Cache::has($this->msisdn.'kisWaterPinConfirm') && Cache::get($this->msisdn.'kisWaterPinConfirm') != ""){
				$pin = Cache::get($this->msisdn.'kisWaterPinConfirm');
				$Selected = Cache::get($this->msisdn.'KisWaterAccounts');
				$Accounts = explode(',',Cache::get($this->msisdn.'ACCOUNTS'));
				$CustomerID = Cache::get($this->msisdn.'CUSTOMERID');
				$Index = $Selected - 1;
				$Account = $Accounts[$Index];
				$utilityAccount = Cache::get($this->msisdn.'KIS');
				$Amount = Cache::get($this->msisdn.'KISformAmount');
				
				$DataToSend = 'FORMID:M-:MERCHANTID:007001020:BANKACCOUNTID:'.$Account.':ACCOUNTID:'. $utilityAccount .':CUSTOMERID:'. $CustomerID .':MOBILENUMBER:'.$this->msisdn.':BANKNAME:'.$this->BankName.':SHORTCODE:'.$this->shortcode.':BANKID:'. $this->DefaultBankID .':COUNTRY:'. $this->Country .':AMOUNT:'.$Amount.':TMPIN:'.$pin.':ACTION:PAYBILL:QUICKPAY:NO:UNIQUEID:'.$this->guid().':TRXSOURCE:USSD:';
				$this->logErr($this->msisdn, "KIS WATER REQUEST : ". $DataToSend);
				$ElmaResponse = $this->ElmaU($DataToSend);
				$responseData = explode(':', strip_tags($ElmaResponse));
				$this->logErr($this->msisdn, "KIS WATER RESPONSE : ". strip_tags($ElmaResponse));
				
				if(count($responseData) < 2){
					$response = (object) array('id' => 'kisWaterPinConfirm', 'action' => 'end', 'response' => "There was a problem processing your request. Please try again later:\n00.Back\n0.Main Menu", 'map' => array( (object) array('menu' => 'kisWaterPinConfirm')), 'type' => 'static');
				}else{
					if($responseData[1] == "000" || $responseData[1] == "OK"){
						$response = (object) array('id' => 'KisWaterValidate', 'action' => 'con', 'response' => $responseData[3], 'map' => array( (object) array('menu' => 'kisWaterPinConfirm')), 'type' => 'form');
					}else{
						$response = (object) array('id' => 'kisWaterPinConfirm', 'action' => 'end', 'response' => $responseData[3].":\n00.Back\n0.Main Menu", 'map' => array( (object) array('menu' => 'kisWaterPinConfirm')), 'type' => 'static');
					}
				}
				
			}else{
				$response = (object) array('id' => 'kisWaterPinConfirm', 'action' => 'con', 'response' => "Enter your PIN to complete transaction:", 'map' => array( (object) array('menu' => 'kisWaterPinConfirm')), 'type' => 'form');
			}
		}else{
			Cache::forget($this->msisdn.'utilityAmount');
			Cache::forget($this->msisdn.'utilityType');
			$response = (object) array('id' => 'kisWaterPinConfirm', 'action' => 'con', 'response' => "Transaction request was cancelled: \n0. Home \n000. Logout", 'map' => array( (object) array('menu' => 'kisWaterPinConfirm')), 'type' => 'form');
		}
		
		return $response;
		
	}

}
