<?php

namespace App\UssdTraits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

trait FullStatement
{
	
	public function FStatementAccounts()
	{
		$Accounts = explode(",", Cache::get($this->msisdn.'ACCOUNTS'));
		$message = "Select Account:\n";
		
		$num = 1;
		foreach($Accounts as $Account){
			if($Account != ""){
				$message .= $num.". ".$Account."\n";
				$num++;
			}
		}
		
		$response = (object) array('id' => 'FStatementAccounts', 'action' => 'con', 'response' => $message, 'map' => array( (object) array('menu' => 'FSatementFrom')), 'type' => 'form');
		
		return $response;
	}
	
	public function AccountFStatement(){
        $Accounts = explode(",", Cache::get($this->msisdn.'ACCOUNTS'));
		$Index = Cache::get($this->msisdn.'FStatementAccounts') - 1;
        $BankAccountID = $Accounts[$Index];
        $CustomerID = Cache::get($this->msisdn.'CUSTOMERID');
		$From = str_replace("iiii", "/", Cache::get($this->msisdn.'FSatementFrom'));
		$To = str_replace("iiii", "/", Cache::get($this->msisdn.'FSatementTo'));
        $DataToSend = 'FORMID:B-:MERCHANTID:FULLSTATEMENT:BANKACCOUNTID:'.$BankAccountID.':INFOFIELD1:'.$From.':INFOFIELD2:'.$To.':CUSTOMERID:'.$CustomerID.':MOBILENUMBER:'.$this->msisdn.':BANKNAME:'.$this->BankName.':BANKID:'.$this->DefaultBankID.':COUNTRY:'.$this->Country.':SHORTCODE:'.$this->shortcode.':UNIQUEID:'.$this->guid().':TRXSOURCE:USSD:';
        $this->logErr($this->msisdn, "FULL STATEMENT REQUEST : ". $DataToSend );
        $ElmaResponse = $this->ElmaU($DataToSend);
        $responseData = explode(':', strip_tags($ElmaResponse));
        $this->logErr($this->msisdn, "FULL STATEMENT RESPONSE : ". strip_tags($ElmaResponse) );

        $response = "";
        if($responseData[1] == "OK"){
            $Statement = str_replace("~", "\n", $responseData[3]);
            $response = (object) array('id' => 'AccountFStatement', 'action' => 'con', 'response' => $Statement.' '.PHP_EOL.'00. Home'. PHP_EOL.'000. Exit', 'map' => array( (object) array('menu' => 'AccountFStatement')), 'type' => 'static');
        }else{
            $response = (object) array('id' => 'AccountFStatement', 'action' => 'end', 'response' => $responseData[3], 'map' => array( (object) array('menu' => 'AccountFStatement')), 'type' => 'static');
        }
        return $response;
    }
	
}