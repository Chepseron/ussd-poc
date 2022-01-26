<?php

namespace App\UssdTraits;

trait Utilities{
    
    /*public function Pin($pin){
        if($pin === "1234"){
            return "STATUS:000:DATA:ELVIS:LOAN LIMIT:5000";
        }else{
            return "STATUS:091:DATA:Invalid Details";
        }
    }*/
    
    public function LoanProducts(){
        return (object) array('action' => 'end', 'response' => 'Processing items', 'map' => array('menu' => 'LoanConfirm'));
    }
    
    public function LoanConfirm(){
        return (object) array('action' => 'end', 'response' => 'Loan has been confirmed and is being processed', 'map' => array('menu' => 'default'));
    }
    
}
