<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\UssdTraits\Menus;
use App\UssdTraits\Dstv;
use App\UssdTraits\StarTimes;
use App\UssdTraits\UmemePostpaid;
use App\UssdTraits\UmemeYaka;
use App\UssdTraits\KisWater;
use App\UssdTraits\Water;
use App\UssdTraits\Balance;
use App\UssdTraits\MiniStatement;
use App\UssdTraits\FullStatement;
use App\UssdTraits\Airtime;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Session;
use Carbon\Carbon;

class UssdController extends Controller {

    use Menus;

use Dstv;

use StarTimes;

use UmemePostpaid;

use UmemeYaka;

use KisWater;

use Water;

use Balance;

use MiniStatement;

use FullStatement;

use Airtime;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $msisdn;
    public $shortcode;
    public $DefaultBankID;
    public $BankName;
    public $Country;
    public $month;
    public $day;
    public $year;

    public function __construct(Request $request) {
        $this->msisdn = $request->msisdn;
        $this->shortcode = $request->shortcode;
        $this->DefaultBankID = env('BANK_ID');
        $this->BankName = env('BANKNAME');
        $this->Country = env('COUNTRY');
    }

    public function mainMenu(Request $request) {
        if (Cache::get($this->msisdn . 'loggedIn')) {
            Cache::forget($this->msisdn . 'menu');
            Cache::forget($this->msisdn . 'KITS');
            $menu = $this->MenuHandler('home');
            $this->logErr($this->msisdn, "MENU HERE : " . $menu->response);
            Cache::add($this->msisdn . 'menu', 'home', 2);
            $menu->action = "con";
            return $menu->action . " " . str_replace('VirtualACC', 'You Qualify for Kes ' . Cache::get($this->msisdn . 'LOANLIMIT') . ' Timiza Loan', $menu->response);
        }
    }

    public function back(Request $request) {
        if (Cache::get($this->msisdn . 'loggedIn')) {
            Cache::forget($this->msisdn . 'menu');
            $menu = $this->MenuHandler('home');
            $this->logErr($this->msisdn, "MENU HERE : " . $menu->response);
            Cache::add($this->msisdn . 'menu', 'home', 2);
            $menu->action = "con";
            return $menu->action . " " . str_replace('VirtualACC', 'You Qualify for Kes ' . number_format(Cache::get($this->msisdn . 'LOANLIMIT')) . ' Timiza Loan', $menu->response);
        }
    }

    public function logout() {
        if (Cache::get($this->msisdn . 'loggedIn')) {
            Cache::forget($this->msisdn . 'menu');
            Cache::forget($this->msisdn . 'KITS');
            $this->logErr($this->msisdn, "Logout Log");
            return "end Thank you for banking with us";
        }
    }

    public function index(Request $request) {
        //
        $menu = "";

        if ($request == "000") {
            if (Cache::get($this->msisdn . 'loggedIn')) {
                Cache::forget($this->msisdn . 'menu');
                Cache::forget($this->msisdn . 'loggedIn');
                Cache::forget($this->msisdn . 'reset');
                $this->logErr($this->msisdn, "Logout Log");
                //return "end Thank you for banking with us";
                return (object) array('id' => 'Exit', 'action' => 'end', 'Thank you for banking with us', 'map' => array((object) array('menu' => 'Exit')), 'type' => 'static');
            }
        }

        Cache::add($this->msisdn . 'msisdn', $request->msisdn, 2);
        if ($request->response == "") {
            //Cache::flush();
            Cache::forget($this->msisdn . 'loggedIn');
            Cache::forget($this->msisdn . 'reset');
            Cache::forget($this->msisdn . "menu");
            Cache::forget($this->msisdn . 'formWaterArea');
            $start_time = microtime(true);
            $getCustomer = $this->GetCustomer($this->msisdn, $this->shortcode);
            $getCustomer = strip_tags($getCustomer);
            $getCustomer = explode(':', $getCustomer);
            $this->logErr($this->msisdn, "GET CUSTOMER" . $getCustomer[1]);
            if ($getCustomer[1] == "000" || $getCustomer[1] == "101") {
                Cache::add($this->msisdn . 'menu', 'menu', 10);
                $end_time = microtime(true);
                $this->logErr($this->msisdn, "GET CUSTOMER TIME : " . (($end_time - $start_time) * 0.000001));
                //return "con Welcome to FTB Bank \n1. Login \n2. Talk to us";
                //return "con Hello ".Cache::get($this->msisdn.'FIRSTNAME').", apply for an instant loan ku simu of up to Ugx.1,000,000 to clear your child school fees, enter your PIN to Continue";
                return "con Welcome to HFB Mobile. Please enter your HFB Mobile PIN number.";
            } else if ($getCustomer[1] == "091") {
                return "end You are not allowed to access this Service.";
                //$response = "Welcome to Mobile Commerce Platform. Please Consult yout Bank to Activate the Services";
                //$action = "end";
                //return $action . " ". $response;
            } else {
                Cache::add($this->msisdn . 'registration', 1, 2);
                //$menu = Cache::add($this->msisdn.'menu', 'TimizaRegistrationValidation', 2);
                $response = "Welcome to Mobile Commerce Platform. Please Consult yout Bank to Activate the Services";
                $action = "end";
                return $action . " " . $response;
            }
        }

        //$menu = Cache::get($this->msisdn.'menu');
        //echo $menu;
        //$isLoggedIn = Cache::has('loggedIn') ? 1 : 0;
        $isLoggedIn = Cache::has($this->msisdn . 'loggedIn') ? 1 : 0;
        $isRegistration = Cache::has($this->msisdn . 'registration') ? 1 : 0;
        $isPinReset = Cache::has($this->msisdn . 'reset') ? 1 : 0;

        $this->logErr($this->msisdn, "MENU : " . Cache::get($this->msisdn . 'menu'));

        $this->logErr($this->msisdn, "MENU INPUT : " . $request->response);
        //echo "LOGGEDIN :".$isLoggedIn;

        if ($isRegistration) {
            //=============================== REGISTRATION ==============================
            if ($request->response != "") {
                $menu = Cache::add($this->msisdn . Cache::get($this->msisdn . 'menu'), $request->response, 2);
                $this->logErr($this->msisdn, "MENU RESPONSES : " . Cache::get($this->msisdn . 'menu') . " -> " . Cache::get(Cache::get($this->msisdn . 'menu')));
                $menu = Cache::get($this->msisdn . 'menu');
                $response = "";
                if (Cache::has($this->msisdn . 'menu')) {
                    $this->logErr($this->msisdn, "DOWN : " . Cache::get($this->msisdn . 'menu'));
                    $menu1 = $this->MenuHandler($menu);
                    $this->logErr($this->msisdn, "DOWN MENU : " . serialize($menu1));
                    //print_r($menu1->type);
                    $menu_type = $menu1->type;
                    if ($menu_type == "dynamic") {
                        $pos = $request->response - 1;
                        if (count($menu1->map) < $pos) {
                            $menu = $this->MenuHandler(Cache::get($this->msisdn . 'menu'));
                            Cache::forget($this->msisdn . 'menu');
                            Cache::add($this->msisdn . 'menu', $menu->id, 2);
                            $response = $menu->action . " " . $menu->response;
                        } else {
                            $menu = $this->MenuHandler($menu1->map[$pos]->menu);
                            Cache::forget($this->msisdn . 'menu');
                            Cache::add($this->msisdn . 'menu', $menu->id, 2);
                            $response = $menu->action . " " . $menu->response;
                        }
                    } else if ($menu_type == "form") {
                        $next_menu = $menu1->map[0]->menu;
                        $menu = $this->MenuHandler($next_menu, $request->response);
                        Cache::forget($this->msisdn . 'menu');
                        Cache::add($this->msisdn . 'menu', $next_menu, 2);
                        $response = $menu->action . " " . $menu->response;
                    } else if ($menu_type == "static") {
                        $response = $menu1->action . " " . $menu1->response;
                    }
                } else {
                    $menu = $this->MenuHandler("TimizaRegistrationValidation");
                    Cache::forget($this->msisdn . 'menu');
                    Cache::add($this->msisdn . 'menu', 'TimizaRegistrationValidation', 2);
                    $response = $menu->action . " " . $menu->response;
                }

                return $response;
            } else {
                $menu = $this->MenuHandler("TimizaRegistrationValidation");
                Cache::forget($this->msisdn . 'menu');
                Cache::add($this->msisdn . 'menu', 'TimizaRegistrationValidation', 2);
                $response = $menu->action . " " . $menu->response;
                return $response;
            }
            //=============================== END REGISTRATION ==========================
        } else if (!$isLoggedIn) {
            $response = "";
            //Cache::forget('menu');
            Cache::forget($this->msisdn . 'menu');

            //session(['menu' => 'pin']);
            Cache::add($this->msisdn . 'menu', 'pin', 2);

            if ($request->response == "") {
                return "con Welcome to Barclays Timiza.Enter your pin to continue";
            } else if (Cache::get($this->msisdn . 'menu') == "pin" && $request->response == "1") {
                return "con Hello " . Cache::get($this->msisdn . 'FIRSTNAME') . ", Welcome to FTB Mobile Banking. Please enter your PIN to proceed";
            } else if (Cache::get($this->msisdn . 'menu') == "pin" && $request->response == "3") {
                return "con 1. Call us on 0711058000 \n2. E-mail us on talktous@FTBbank.co.ke \n3. Chat us on FaceBook - @FTBBank \n4. Chat us on Twitter -@FTBbank \n5. Chat us on Instagram- @FTBbank \n000. Exit";
            } else if (Cache::has($this->msisdn . 'GetCustomerSecurityQuestion') && $request->response != "") {
                return $this->pinReset($request->response);
            } else {
                $validationResponse = $this->Pin($this->msisdn, $this->shortcode, $request->response);
                if ($validationResponse != "") {

                    if ($validationResponse === "000") {
                        Cache::forget($this->msisdn . 'menu');
                        Cache::add($this->msisdn . 'pin', $request->response, 2);
                        Cache::add($this->msisdn . 'menu', 'home', 2);
                        Cache::add($this->msisdn . 'loggedIn', 1, 2);
                        $res = $this->MenuHandler(Cache::get($this->msisdn . 'menu'));
                        $res->action = "con";
                        $message = "";
                        if (Cache::get($this->msisdn . 'LOANLIMIT') > 0) {
                            $message = str_replace('VirtualACC', 'You Qualify for Kes ' . number_format(Cache::get($this->msisdn . 'LOANLIMIT')) . ' Timiza Loan', $res->response);
                        } else {
                            $message = str_replace('VirtualACC', 'Select:' . PHP_EOL, $res->response);
                            ;
                        }
                        return $res->action . " " . $message;
                    } else if ($validationResponse === "101") {
                        Cache::forget($this->msisdn . 'menu');
                        Cache::add($this->msisdn . 'pin', $request->response, 2);
                        Cache::add($this->msisdn . 'menu', 'newPin11', 2);
                        Cache::add($this->msisdn . 'loggedIn', 1, 2);
                        $res = $this->MenuHandler(Cache::get($this->msisdn . 'menu'));
                        $res->action = "con";
                        return $res->action . " " . $res->response;
                    } else if ($validationResponse === "091") {
                        Cache::forget($this->msisdn . 'menu');
                        Cache::add($this->msisdn . 'menu', 'pin', 2);
                        $response = 'con Wrong Pin. Enter your pin to Continue';
                        return $response;
                    } else if ($validationResponse === "102") {
                        Cache::forget($this->msisdn . 'menu');
                        $response = "end Hello, " . Cache::get($this->msisdn . 'FIRSTNAME') . " your PIN is blocked.\nTo reset a new PIN use the forgot PIN option";
                        return $response;
                    } else if ($validationResponse === "104") {
                        Cache::forget($this->msisdn . 'menu');
                        $response = "end We are verifying your details at the moment, we will get back you shortly";
                        return $response;
                    } else if ($validationResponse === "105") {
                        Cache::forget($this->msisdn . 'menu');
                        $response = "end Hello, we are unable to verify your details.";
                        return $response;
                    } else if ($validationResponse === "106") {
                        Cache::forget($this->msisdn . 'menu');
                        Cache::add($this->msisdn . 'pin', $request->response, 2);
                        Cache::add($this->msisdn . 'menu', 'GetBankQuestions', 2);
                        Cache::add($this->msisdn . 'loggedIn', 1, 2);
                        $res = $this->MenuHandler(Cache::get($this->msisdn . 'menu'));
                        $res->action = "con";
                        return $res->action . " " . $res->response;
                    }
                } else {
                    $response = "con Error";
                }
            }

            return $response;
        } else {
            //echo "After login :". Cache::get('menu');
            $this->logErr($this->msisdn, "UP : " . Cache::get($this->msisdn . 'menu'));
            if ($request->response != "") {
                if (Cache::has($this->msisdn . Cache::get($this->msisdn . 'menu'))) {
                    Cache::forget($this->msisdn . Cache::get($this->msisdn . 'menu'));
                }
                $menu = Cache::add($this->msisdn . Cache::get($this->msisdn . 'menu'), $request->response, 3);
                $this->logErr($this->msisdn, "MENU RESPONSES : " . Cache::get($this->msisdn . 'menu') . " -> " . Cache::get(Cache::get($this->msisdn . 'menu')));
                $menu = Cache::get($this->msisdn . 'menu');
                $response = "";
                if (Cache::has($this->msisdn . 'menu')) {
                    Cache::forget($this->msisdn . 'menu');
                    Cache::add($this->msisdn . 'menu', 'pinReset', 2);
                    $this->logErr($this->msisdn, "DOWN : " . Cache::get($this->msisdn . 'menu'));
                    $menu1 = $this->MenuHandler($menu);
                    $this->logErr($this->msisdn, "DOWN MENU : " . serialize($menu1));
                    //print_r($menu1->type);
                    $menu_type = $menu1->type;
                    if ($request->response === "0") {
                        Cache::forget($this->msisdn . 'menu');
                        Cache::add($this->msisdn . 'menu', 'home', 2);
                    }
                    if ($menu_type == "dynamic") {
                        $pos = $request->response - 1;
                        if (count($menu1->map) < $request->response) {
                            $menu = $this->MenuHandler(Cache::get($this->msisdn . 'menu'));
                            Cache::forget($this->msisdn . 'menu');
                            Cache::add($this->msisdn . 'menu', $menu->id, 3);
                            $response = $menu->action . " " . $menu->response;
                        } else {
                            $menu = $this->MenuHandler($menu1->map[$pos]->menu);
                            Cache::forget($this->msisdn . 'menu');
                            Cache::add($this->msisdn . 'menu', $menu->id, 3);
                            $response = $menu->action . " " . $menu->response;
                        }
                    } else if ($menu_type == "form") {
                        $next_menu = $menu1->map[0]->menu;
                        $menu = $this->MenuHandler($next_menu, $request->response);
                        Cache::forget($this->msisdn . 'menu');
                        Cache::add($this->msisdn . 'menu', $next_menu, 3);
                        $response = $menu->action . " " . $menu->response;
                    } else if ($menu_type == "static") {
                        $response = $menu1->action . " " . $menu1->response;
                    }
                } else {
                    $menu = $this->MenuHandler("home");
                    Cache::forget($this->msisdn . 'menu');
                    Cache::add($this->msisdn . 'menu', 'home', 2);
                    $response = $menu->action . " " . $menu->response;
                }

                return $response;
            } else {
                return "SDFSDfsdf";
            }
        }
    }

    public function MenuHandler($menu, $response = null) {
        //
        $menus = json_decode($this->menu());
        //var_dump($menu->menu);

        $neededObject = array_filter(
                $menus->menu, function ($e) use ($menu) {
            return $e->id == $menu;
        }
        );

        //$this->logErr($this->msisdn, "MENU : ". json_encode($neededObject));
        //$this->logErr($this->msisdn, "MENU RESPONSE : ". Cache::get('menu') . " : " . (Cache::has(Cache::get('menu')) ? Cache::get(Cache::get('menu')) : ""));

        if (count(array_values($neededObject)) == 0) {
            $this->logErr($this->msisdn, "ERROR : " . $menu);
            try {
                if (method_exists($this, "LoanProducts")) {

                    $this->logErr($this->msisdn, "MMMMMMM : " . $menu);

                    if (strpos($menu, $this->msisdn)) {
                        $menu = substr($menu, 12);
                    }

                    $res = $this->$menu();

                    if ($res->action == "con") {
                        Cache::forget($this->msisdn . 'menu');
                        //session(['menu' => $res->map[0]->menu]);
                        Cache::add($this->msisdn . 'menu', 'groupsave', 2);
                        return $res;
                    } else if ($res->action == "end") {
                        Cache::forget($this->msisdn . 'menu');
                        //return (object) array('action' => 'end', 'response' => $res->response, 'map' => array('menu' => 'groupsave'));
                        return $res;
                    } else {
                        return (object) array('action' => 'end', 'response' => 'Something went wrong', 'map' => array('menu' => 'groupsave'));
                    }
                } else {
                    
                }
            } catch (Exception $ex) {
                return (object) array('action' => 'end', 'response' => 'Something went wrong', 'map' => array('menu' => 'groupsave'));
            }
        } else {
            return array_values($neededObject)[0];
        }
    }

    public function ResponseHandler($request) {
        if (Cache::has($this->msisdn . 'menu')) {
            $menuItem = Session::get($this->msisdn . 'menu');
            $menu = $this->MenuHandler($menuItem);
        }
    }

    public function guid() {
        if (function_exists('com_create_guid')) {
            return com_create_guid();
        } else {
            mt_srand((double) microtime() * 10000); //optional for php 4.2.0 and up.
            $charid = strtolower(md5(uniqid(rand(), true)));
            $hyphen = chr(45); // "-"
            $uuid = //chr(123)// "{"
                    ""
                    . substr($charid, 0, 8) . $hyphen
                    . substr($charid, 8, 4) . $hyphen
                    . substr($charid, 12, 4) . $hyphen
                    . substr($charid, 16, 4) . $hyphen
                    . substr($charid, 20, 12)
                    . ""; //.chr(125);// "}"
            return $uuid;
        }
    }

    public function Charges($MerchantID, $Amount) {
        $CustomerID = Cache::get($this->msisdn . 'CUSTOMERID');
        $DataToSend = 'FORMID:O-GetBankMerchantCharges:BANKID:66:MERCHANTID:' . $MerchantID . ':AMOUNT:' . $Amount . ':BANKNAME:HFB:CUSTOMERID:' . $CustomerID . ':MOBILENUMBER:' . $this->msisdn . ':SHORTCODE:' . $this->shortcode . ':COUNTRY:' . $this->Country . ':TRXSOURCE:USSD:';
        $this->logErr($this->msisdn, "CHARGES REQUEST :" . $DataToSend);
        $ElmaResponse = $this->ElmaU($DataToSend);
        $this->logErr($this->msisdn, "CHARGES RESPONSE : " . strip_tags($ElmaResponse));
        $ElmaResponse = $ElmaResponse;
        $responseData = explode(':', strip_tags($ElmaResponse));
        try {
            $charge = "";
            if ($responseData[1] == "000" || $responseData[1] == "OK") {
                $chargeData = explode('|', $responseData[3]);
                $charge = str_replace(',', '', $chargeData[1]);
            }
            return $charge;
        } catch (\Exception $ex) {
            return "0";
            //return (object) array('action' => 'end', 'response' => 'Enter your Account number:\n00.Back\n0.Main Menu', 'map' => array( (object) array('menu' => 'ServiceDown')), 'type' => 'static');
        }
    }

    public function GetCustomerLoanLimit() {
        $CustomerID = Cache::get($this->msisdn . 'CUSTOMERID');
        $Account = Cache::get($this->msisdn . 'ACCOUNT');
        $DataToSend = 'FORMID:M-:MERCHANTID:INDIVIDUALACTIVITY:INFOFIELD1:INDIVIDUALACTIVITY:INFOFIELD2:SAFARICOM:INFOFIELD3:DEFAULTLIMIT:INFOFIELD9:' . $this->msisdn . ':METERNUMBER::BANKNAME:BARCLAYS:CUSTOMERID:' . $CustomerID . ':MOBILENUMBER:' . $this->msisdn . ':ACCOUNTID:' . $Account . ':BANKID:03:ACTION:GETNAME:COUNTRY:KENYA:';
        $this->logErr($this->msisdn, "GET CUSTOMER LOAN LIMIT REQUEST :" . $DataToSend);
        $ElmaResponse = $this->ElmaU($DataToSend);
        $this->logErr($this->msisdn, "GET CUSTOMER LOAN LIMIT RESPONSE : " . strip_tags($ElmaResponse));
        $ElmaResponse = $ElmaResponse;
        $responseData = explode(':', strip_tags($ElmaResponse));
        try {
            $limit = "";
            if ($responseData[1] == "000") {
                $limitData = explode('|', $responseData[3]);
                $limit = str_replace(',', '', $limitData[1]);
            }

            return $limit;
        } catch (\Exception $ex) {
            return "0";
            //return (object) array('action' => 'end', 'response' => 'Enter your Account number:\n00.Back\n0.Main Menu', 'map' => array( (object) array('menu' => 'ServiceDown')), 'type' => 'static');
        }
    }

    public function GetCustomer($msisdn, $shortcode) {
        $DataToSend = 'FORMID:GETCUSTOMER:MOBILENUMBER:' . $msisdn . ':SHORTCODE:' . $shortcode . ':COUNTRY:' . $this->Country . ':DEVICEID:' . $msisdn . $shortcode . ':UNIQUEID:' . $this->guid() . ':';
        $this->logErr($this->msisdn, "GET CUSTOMER REQUEST :" . $DataToSend);
        $ElmaResponse = $this->ElmaU($DataToSend);
        $this->logErr($this->msisdn, "GET CUSTOMER RESPONSE : " . strip_tags($ElmaResponse));
        $ElmaResponse = $ElmaResponse;
        //dd($ElmaResponse);
        $responseData = explode(':', strip_tags($ElmaResponse));

        try {
            if ($responseData[1] == "000" || $responseData[1] == "101") {
                Cache::add($this->msisdn . 'FIRSTNAME', $responseData[7], 2);
                Cache::add($this->msisdn . 'LASTNAME', $responseData[9], 2);
                if (Cache::has($this->msisdn . 'CUSTOMERID')) {
                    Cache::forget($this->msisdn . 'CUSTOMERID');
                }
                Cache::add($this->msisdn . 'CUSTOMERID', $responseData[3], 3);
                Cache::add($this->msisdn . 'BANKNAME', $responseData[11], 2);
            }

            return $ElmaResponse;
        } catch (\Exception $ex) {
            return "Error";
            //return (object) array('action' => 'end', 'response' => 'Enter your Account number:\n00.Back\n0.Main Menu', 'map' => array( (object) array('menu' => 'ServiceDown')), 'type' => 'static');
        }
    }

    public function pinReset($answer) {
        $BankAccountID = Cache::get($this->msisdn . 'ACCOUNTS');
        //$Answer = Cache::get($this->msisdn.'GetCustomerSecurityQuestion');
        $Answer = $answer;
        $CustomerID = Cache::get($this->msisdn . 'CUSTOMERID');
        $DataToSend = 'FORMID:RESETPIN2:BANKID:' . $this->DefaultBankID . ':ANSWER:' . $Answer . ':SHORTCODE:' . $this->shortcode . ':CUSTOMERID:' . $CustomerID . ':MOBILENUMBER:' . $this->msisdn . ':COUNTRY:' . $this->Country . ':UNIQUEID:' . $this->guid() . ':TRXSOURCE:USSD:';
        $this->logErr($this->msisdn, "PIN RESET REQUEST : " . $DataToSend);
        $ElmaResponse = $this->ElmaU($DataToSend);
        $responseData = explode(':', strip_tags($ElmaResponse));
        $this->logErr($this->msisdn, "PIN RESET RESPONSE : " . strip_tags($ElmaResponse));

        Cache::forget($this->msisdn . 'GetCustomerSecurityQuestion');
        $response = "";
        if ($responseData[1] == "000") {
            $FirstName = Cache::get($this->msisdn . 'FIRSTNAME');
            $message = 'Hello, ' . strtoupper($FirstName) . ' you have successfully reset your PIN. You will receive a new PIN shortly via SMS.';
            $response = (object) array('id' => 'pinReset', 'action' => 'end', 'response' => $message, 'map' => array((object) array('menu' => 'pinReset')), 'type' => 'static');
        } else {
            $response = (object) array('id' => 'pinReset', 'action' => 'end', 'response' => $responseData[3], 'map' => array((object) array('menu' => 'pinReset')), 'type' => 'static');
        }

        return $response->action . " " . $response->response;
    }

    public function Pin($msisdn, $shortcode, $pin) {
        $CustomerID = Cache::get($this->msisdn . 'CUSTOMERID');
        $DataToSend = 'FORMID:LOGIN:CUSTOMERID:' . $CustomerID . ':MOBILENUMBER:' . $this->msisdn . ':BANKID:' . $this->DefaultBankID . ':BANKNAME:' . $this->BankName . ':SHORTCODE:' . $this->shortcode . ':COUNTRY:' . $this->Country . ':DEVICEID:' . $msisdn . $shortcode . ':LOGINMPIN:' . $pin . ':UNIQUEID:' . $this->guid() . ':';
        //$this->Logger($msisdn, $DataToSend);
        $this->logErr($this->msisdn, "LOGIN REQUEST : " . strip_tags($DataToSend));
        $ElmaResponse = $this->ElmaU($DataToSend);
        $this->logErr($this->msisdn, "LOGIN RESPONSE :" . strip_tags($ElmaResponse));
        $responseData = explode(':', strip_tags($ElmaResponse));

        if ($responseData[1] == "000" || $responseData[1] == "101" || $responseData[1] == "102") {
            if (Cache::has($this->msisdn . 'ACCOUNTS')) {
                Cache::forget($this->msisdn . 'ACCOUNTS');
            }
            $AccountsRaw = explode(":", $responseData[3]);
            $Accounts = array();
            foreach ($AccountsRaw as $Account) {
                if ($Account != "") {
                    $Accounts[] = $Account;
                }
            }
            $this->logErr($this->msisdn, "LOGIN ACCOUNTS :" . implode(":", $Accounts));
            Cache::add($this->msisdn . 'ACCOUNTS', implode(":", $Accounts), 3);
        }

        return $responseData[1];
    }

    public function MobileMoneyAccounts() {
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

            $response = (object) array('id' => 'MobileMoneyAccounts', 'action' => 'con', 'response' => $message, 'map' => array((object) array('menu' => 'MobileMoneyConfirm')), 'type' => 'form');
        } else {
            $response = (object) array('action' => 'con', 'response' => 'You dont have any accounts', 'map' => array((object) array('menu' => 'home')), 'type' => 'form');
        }

        return $response;
    }

    public function MobileMoneyOtherAccounts() {
        $all_accounts = Cache::get($this->msisdn . 'ACCOUNTS');
        $Accounts = explode(',', $all_accounts);

        if (count($Accounts) > 0) {
            $message = "Select Source Account \n";
            $num = 1;
            foreach ($Accounts as $Account) {
                if ($Account != "") {
                    $message .= $num . ". " . $Account . "\n";
                    $num++;
                }
            }
            $message .= "00. Home \n 000. Exit";
            $response = (object) array('id' => 'MobileMoneyOtherAccounts', 'action' => 'con', 'response' => $message, 'map' => array((object) array('menu' => 'AccToMpesaRecipient')), 'type' => 'form');
        } else {
            $response = (object) array('action' => 'con', 'response' => 'You dont have any accounts', 'map' => array((object) array('menu' => 'home')), 'type' => 'form');
        }
        return $response;
    }

    public function MobileMoneyConfirm() {
        $otherPhone = Cache::has($this->msisdn . 'formAccountMobile') ? Cache::get($this->msisdn . 'formAccountMobile') : "";
        $phone = "";
        if ($otherPhone != "") {
            $phone = "256" . substr($otherPhone, 1);
        } else {
            $phone = $this->msisdn;
        }
        Cache::add($this->msisdn . 'MMPHONE', $phone, 2);
        $Amount = Cache::get($this->msisdn . 'formAmountMM');
        $message = "Transfer " . $Amount . " KES to mobile money number " . $phone . " Reply with: \n1. Accept \n2. Cancel";
        $response = (object) array('id' => 'MobileMoneyConfirm', 'action' => 'con', 'response' => $message, 'map' => array((object) array('menu' => 'MobileMoneyPinConfirm')), 'type' => 'form');
        return $response;
    }

    public function MobileMoneyPinConfirm() {
        if (Cache::has($this->msisdn . 'MobileMoneyConfirm') && Cache::get($this->msisdn . 'MobileMoneyConfirm') == "1") {
            if (Cache::has($this->msisdn . 'MobileMoneyPinConfirm') && Cache::get($this->msisdn . 'MobileMoneyPinConfirm') != "") {
                $phone = Cache::has($this->msisdn . 'MMPHONE') ? Cache::get($this->msisdn . 'MMPHONE') : "";
                $Amount = Cache::get($this->msisdn . 'formAmountMM');
                $response = (object) array('id' => 'MobileMoneyPinConfirm', 'action' => 'end', 'response' => "Your mobile money transfer of amount " . $Amount . " To " . $phone . " From account POC/2345678 Was successful. thank you for banking with us " . PHP_EOL . '00. Home' . PHP_EOL . '000. Exit', 'map' => array((object) array('menu' => 'MobileMoneyPinConfirm')), 'type' => 'static');
                return $response;
            } else {
                return (object) array('id' => 'MobileMoneyPinConfirm', 'action' => 'con', 'response' => 'Enter Mobile PIN', 'map' => array((object) array('menu' => 'MobileMoneyPinConfirm')), 'type' => 'form');
            }
        } else {
            return (object) array('id' => 'MobileMoneyPinConfirm', 'action' => 'con', 'response' => 'Transaction request was canceled ' . PHP_EOL . '0. Home ' . PHP_EOL . '000. Exit', 'map' => array((object) array('menu' => 'groupsave')), 'type' => 'form');
        }
    }

    public function MMBillsAccounts() {
        $all_accounts = Cache::get($this->msisdn . 'ACCOUNTS');
        $Accounts = explode(',', $all_accounts);

        if (count($Accounts) > 0) {
            $message = "Select Source Account \n";

            $num = 1;
            foreach ($Accounts as $Account) {
                if ($Account != "") {
                    $message .= $num . ". " . $Account . "\n";
                    $num++;
                }
            }
            $message .= "00. Home \n 000. Exit";

            $SelectedItem = Cache::get($this->msisdn . 'MobileMoney');
            if ($SelectedItem == "3") {
                $response = (object) array('id' => 'MMBillsAccounts', 'action' => 'con', 'response' => $message, 'map' => array((object) array('menu' => 'BuyFloatAgent')), 'type' => 'form');
            }if ($SelectedItem == "4") {
                $response = (object) array('id' => 'MMBillsAccounts', 'action' => 'con', 'response' => $message, 'map' => array((object) array('menu' => 'BuyGoodsTill')), 'type' => 'form');
            } else if ($SelectedItem == "5") {
                $response = (object) array('id' => 'MMBillsAccounts', 'action' => 'con', 'response' => $message, 'map' => array((object) array('menu' => 'PayBillNumber')), 'type' => 'form');
            }
        } else {
            $response = (object) array('action' => 'con', 'response' => 'You dont have any accounts', 'map' => array((object) array('menu' => 'home')), 'type' => 'form');
        }

        return $response;
    }

    public function PhoneLoanAccount() {
        $all_accounts = Cache::get($this->msisdn . 'ACCOUNTS');
        $Accounts = explode(',', $all_accounts);

        if (count($Accounts) > 0) {
            $message = "Select bank \n";

            $num = 1;
            foreach ($Accounts as $Account) {
                if ($Account != "") {
                    $message .= $num . ". " . $Account . "\n";
                    $num++;
                }
            }
            $message .= "00. Home \n 000. Exit";

            $response = (object) array('id' => 'PhoneLoanAccount', 'action' => 'con', 'response' => $message, 'map' => array((object) array('menu' => 'PhoneLoanTerms')), 'type' => 'form');
        } else {
            $response = (object) array('action' => 'con', 'response' => 'You dont have any accounts', 'map' => array((object) array('menu' => 'home')), 'type' => 'form');
        }

        return $response;
    }

    public function PhoneLoanTerms() {
        $message = "Accept Terms https://www.housingfinance.co.ug: \n1. Accept \n2. Cancel";
        $response = (object) array('id' => 'PhoneLoanTerms', 'action' => 'con', 'response' => $message, 'map' => array((object) array('menu' => 'PhoneLoanConfirm')), 'type' => 'form');

        return $response;
    }

    public function PhoneLoanConfirm() {
        $terms = Cache::get($this->msisdn . 'PhoneLoanTerms');
        if ($terms == "1") {
            $LoanAmount = Cache::get($this->msisdn . 'LoanAmount');
            $Period = Cache::get($this->msisdn . 'LoanTerm');
            $LoanPeriod = "";

            switch ($Period) {
                case "1":
                    $LoanPeriod = "1 Month";
                    break;
                case "2":
                    $LoanPeriod = "2 Months";
                    break;
                case "3":
                    $LoanPeriod = "3 Months";
                    break;
            }

            $CustomerID = Cache::get($this->msisdn . 'CUSTOMERID');
            $DataToSend = 'FORMID:O-LOANAPPLNFTB:LOANAMOUNT:' . $LoanAmount . ':LOANPERIOD:' . $LoanPeriod . ':CUSTOMERID:' . $CustomerID . ':MOBILENUMBER:' . $this->msisdn . ':BANKID:' . $this->DefaultBankID . ':BANKNAME:' . $this->BankName . ':SHORTCODE:' . $this->shortcode . ':COUNTRY:' . $this->Country . ':UNIQUEID:' . $this->guid() . ':TRXSOURCE:USSD:';
            $this->logErr($this->msisdn, "LOAN CALC REQUEST : " . $DataToSend);
            $ElmaResponse = $this->ElmaU($DataToSend);
            $this->logErr($this->msisdn, "LOAN CALC RESPONSE : " . strip_tags($ElmaResponse));

            $responseData = explode(':', strip_tags($ElmaResponse));


            if ($responseData[1] == "000") {
                $LoanData = explode('|', $responseData[3]);
                $Data = "\n" . $LoanData[0] . " - " . $LoanData[1] . "\n" . $LoanData[2] . " - " . $LoanData[3] . "\n" . $LoanData[4] . " - " . $LoanData[5] . "\n" . $LoanData[6] . " - " . $LoanData[7] . "\n" . $LoanData[8] . " - " . $LoanData[9];
                $message = "Loan Amount - " . $LoanAmount . " \n Period - " . $LoanPeriod . " \n " . $Data . ": \n1. Accept \n2. Cancel";
                $response = (object) array('id' => 'PhoneLoanConfirm', 'action' => 'con', 'response' => $message, 'map' => array((object) array('menu' => 'PhoneLoanPinConfirm')), 'type' => 'form');
            } else {
                $response = (object) array('id' => 'PhoneLoanConfirm', 'action' => 'end', 'response' => 'There was a problem processing your request. Please try again.', 'map' => array((object) array('menu' => 'PhoneLoanConfirm')), 'type' => 'static');
            }
        } else {
            $response = (object) array('id' => 'PhoneLoanConfirm', 'action' => 'end', 'response' => 'Transaction request was cancelled.', 'map' => array((object) array('menu' => 'PhoneLoanConfirm')), 'type' => 'static');
        }

        return $response;
    }

    public function EFTAccounts() {
        $all_accounts = Cache::get($this->msisdn . 'ACCOUNTS');
        $Accounts = explode(',', $all_accounts);

        if (count($Accounts) > 0) {
            $message = "Select Bank \n";

            $num = 1;
            foreach ($Accounts as $Account) {
                if ($Account != "") {
                    $message .= $num . ". HFB/" . $Account . "\n";
                    $num++;
                }
            }
            $message .= "00. Home \n 000. Exit";

            $response = (object) array('id' => 'EFTAccounts', 'action' => 'con', 'response' => $message, 'map' => array((object) array('menu' => 'EFTBanksFilter')), 'type' => 'form');
        } else {
            $response = (object) array('action' => 'con', 'response' => 'You dont have any accounts', 'map' => array((object) array('menu' => 'home')), 'type' => 'form');
        }

        return $response;
    }

    public function PhoneLoanPinConfirm() {
        $start_time = microtime(true);
        if (Cache::has($this->msisdn . 'PhoneLoanConfirm') && Cache::get($this->msisdn . 'PhoneLoanConfirm') == "1") {
            if (Cache::has($this->msisdn . 'PhoneLoanPinConfirm') && Cache::get($this->msisdn . 'PhoneLoanPinConfirm') != "") {
                $trx_start = microtime(true);
                $pin = Cache::get($this->msisdn . 'PhoneLoanPinConfirm');

                $Selected = Cache::get($this->msisdn . 'PhoneLoanAccount');
                $Accounts = explode(',', Cache::get($this->msisdn . 'ACCOUNTS'));
                $CustomerID = Cache::get($this->msisdn . 'CUSTOMERID');
                $Index = $Selected - 1;
                $Account = $Accounts[$Index];
                $LoanAmount = Cache::get($this->msisdn . 'LoanAmount');
                $LoanPeriod = Cache::get($this->msisdn . 'LoanTerm');

                $CustomerID = Cache::get($this->msisdn . 'CUSTOMERID');
                $DataToSend = 'FORMID:B-:MERCHANTID:LOANAPPLICATION:AMOUNT:' . $LoanAmount . ':INFOFIELD1:' . $LoanPeriod . ':CUSTOMERID:' . $CustomerID . ':MOBILENUMBER:' . $this->msisdn . ':BANKACCOUNTID:' . $Account . ':BANKID:' . $this->DefaultBankID . ':BANKNAME:' . $this->BankName . ':SHORTCODE:' . $this->shortcode . ':COUNTRY:' . $this->Country . ':TMPIN:' . $pin . ':UNIQUEID:' . $this->guid() . ':TRXSOURCE:USSD:';
                $this->logErr($this->msisdn, "MOBILE LOAN REQUEST : " . $DataToSend);
                $ElmaResponse = $this->ElmaU($DataToSend);
                $this->logErr($this->msisdn, "MOBILE LOAN RESPONSE : " . strip_tags($ElmaResponse));
                $responseData = explode(':', strip_tags($ElmaResponse));

                $trx_end = microtime(true);
                Log::notice(Carbon::now() . " - " . $this->msisdn . "- MOBILE LOAN TRANSACTION TIME : " . ($trx_start - $trx_end));

                $response = "";
                if ($responseData[1] == "000") {
                    $response = (object) array('id' => 'PhoneLoanPinConfirm', 'action' => 'end', 'response' => $responseData[3] . PHP_EOL . '00. Home' . PHP_EOL . '000. Exit', 'map' => array((object) array('menu' => 'PhoneLoanPinConfirm')), 'type' => 'static');
                } else {
                    $response = (object) array('id' => 'PhoneLoanPinConfirm', 'action' => 'end', 'response' => urldecode($responseData[3]), 'map' => array((object) array('menu' => 'PhoneLoanPinConfirm')), 'type' => 'static');
                }
                return $response;
            } else {
                $end_time = microtime(true);
                $this->logErr($this->msisdn, "MOBILE LOAN TRANSACTION TIME : " . ($start_time - $end_time));
                return (object) array('id' => 'PhoneLoanPinConfirm', 'action' => 'con', 'response' => 'Enter your Pin to complete transaction:', 'map' => array((object) array('menu' => 'PhoneLoanPinConfirm')), 'type' => 'form');
            }
        } else {
            $end_time = microtime(true);
            $this->logErr($this->msisdn, "MOBILE LOAN TRANSACTION TIME : " . ($start_time - $end_time));
            return (object) array('id' => 'PhoneLoanPinConfirm', 'action' => 'con', 'response' => 'Transaction request was canceled ' . PHP_EOL . '0. Home ' . PHP_EOL . '000. Exit', 'map' => array((object) array('menu' => 'groupsave')), 'type' => 'form');
        }
    }

    public function RTGSAccounts() {
        $all_accounts = Cache::get($this->msisdn . 'ACCOUNTS');
        $Accounts = explode(',', $all_accounts);

        if (count($Accounts) > 0) {
            $message = "Select Source Account \n";

            $num = 1;
            foreach ($Accounts as $Account) {
                if ($Account != "") {
                    $message .= $num . ". " . $Account . "\n";
                    $num++;
                }
            }
            $message .= "00. Home \n 000. Exit";

            $response = (object) array('id' => 'RTGSAccounts', 'action' => 'con', 'response' => $message, 'map' => array((object) array('menu' => 'RTGSBanksFilter')), 'type' => 'form');
        } else {
            $response = (object) array('action' => 'con', 'response' => 'You dont have any accounts', 'map' => array((object) array('menu' => 'home')), 'type' => 'form');
        }

        return $response;
    }

    public function RecipientBanksFetcherRTGS() {
        $trx_start = microtime(true);
        $CustomerID = Cache::get($this->msisdn . 'CUSTOMERID');
        $BankName = "FTB";

        $Selected = Cache::get($this->msisdn . 'RTGSBanksFilter');
        $Filter = "";
        if ($Selected == "1") {
            $Filter = "A-B";
        } else if ($Selected == "2") {
            $Filter = "C";
        } else if ($Selected == "3") {
            $Filter = "D-E";
        } else if ($Selected == "4") {
            $Filter = "F";
        } else if ($Selected == "5") {
            $Filter = "G-H";
        } else if ($Selected == "6") {
            $Filter = "I-M";
        } else if ($Selected == "7") {
            $Filter = "N-S";
        } else if ($Selected == "8") {
            $Filter = "T-Z";
        }

        $DataToSend = 'FORMID:O-GetCommercialBankWithFilter:FILTER:' . $Filter . ':CUSTOMERID:' . $CustomerID . ':MOBILENUMBER:' . $this->msisdn . ':SHORTCODE:' . $this->shortcode . ':BANKNAME:' . $BankName . ':BANKID:' . $this->DefaultBankID . ':COUNTRY:' . $this->Country . ':TRXSOURCE:USSD:';
        $this->NewLog($this->msisdn, "TRANSFER BANKS REQUEST : " . $DataToSend);
        $ElmaResponse = $this->ElmaU($DataToSend);
        $this->NewLog($this->msisdn, "TRANSFER BANKS RESPONSE : " . strip_tags($ElmaResponse));
        $responseData = explode(':', strip_tags($ElmaResponse));

        $trx_end = microtime(true);
        Log::notice(Carbon::now() . " - " . $this->msisdn . "- TRANSFER BANKS TRANSACTION TIME : " . ($trx_start - $trx_end));
        Cache::add($this->msisdn . 'RTGSBANKS', $responseData[3], 2);
        if (count($responseData) > 0) {
            $message = "Select Bank to transfer to: \n";
            $num = 1;
            $banks = explode("~", $responseData[3]);
            foreach ($banks as $bank) {
                // code...
                $message .= $num . " " . explode("|", $bank)[1] . "\n";
                $num++;
            }
            $response = (object) array('id' => 'RecipientBanksFetcherRTGS', 'action' => 'con', 'response' => $message . ' ' . PHP_EOL . '00. Back ' . PHP_EOL . '000. Exit', 'map' => array((object) array('menu' => 'RTGSBranchFilter')), 'type' => 'form');
        } else {
            $response = (object) array('id' => 'RecipientBanksFetcherRTGS', 'action' => 'con', 'response' => 'Sorry, there are currently no supported banks' . PHP_EOL . '00. Back ' . PHP_EOL . '000. Exit', 'map' => array((object) array('menu' => 'RecipientBanksFetcherEFT')), 'type' => 'form');
        }

        return $response;
    }

    public function RecipientBranchFetcherRTGS() {
        $trx_start = microtime(true);
        $CustomerID = Cache::get($this->msisdn . 'CUSTOMERID');
        $BankName = "FTB";

        $Selected = Cache::get($this->msisdn . 'RTGSBranchFilter');
        $Filter = "";
        if ($Selected == "1") {
            $Filter = "A-B";
        } else if ($Selected == "2") {
            $Filter = "C";
        } else if ($Selected == "3") {
            $Filter = "D-E";
        } else if ($Selected == "4") {
            $Filter = "F";
        } else if ($Selected == "5") {
            $Filter = "G-H";
        } else if ($Selected == "6") {
            $Filter = "I-M";
        } else if ($Selected == "7") {
            $Filter = "N-S";
        } else if ($Selected == "8") {
            $Filter = "T-Z";
        }

        $RTGSBanks = explode("~", Cache::get($this->msisdn . 'RTGSBANKS'));
        $SelectedIndex = Cache::get($this->msisdn . 'RecipientBanksFetcherRTGS');
        $SelectedBank = explode("|", $RTGSBanks[$SelectedIndex - 1]);

        $DataToSend = 'FORMID:O-GetCommercialBankBranchWithFilter:BANKFILTER:' . $SelectedBank[0] . ':FILTER:' . $Filter . ':CUSTOMERID:' . $CustomerID . ':MOBILENUMBER:' . $this->msisdn . ':SHORTCODE:' . $this->shortcode . ':BANKNAME:' . $BankName . ':BANKID:' . $this->DefaultBankID . ':COUNTRY:' . $this->Country . ':TRXSOURCE:USSD:';
        $this->NewLog($this->msisdn, "TRANSFER BANKS REQUEST : " . $DataToSend);
        $ElmaResponse = $this->ElmaU($DataToSend);
        $this->NewLog($this->msisdn, "TRANSFER BANKS RESPONSE : " . strip_tags($ElmaResponse));
        $responseData = explode(':', strip_tags($ElmaResponse));

        $trx_end = microtime(true);
        Log::notice(Carbon::now() . " - " . $this->msisdn . "- TRANSFER BANKS TRANSACTION TIME : " . ($trx_start - $trx_end));
        Cache::add($this->msisdn . 'RTGSBRANCHES', $responseData[3], 2);
        if (count($responseData) > 0) {
            $message = "Select Branch: \n";
            $num = 1;
            $branches = explode("~", $responseData[3]);
            foreach ($branches as $branche) {
                // code...
                $message .= $num . " " . explode("|", $branche)[1] . "\n";
                $num++;
            }
            $response = (object) array('id' => 'RecipientBranchFetcherRTGS', 'action' => 'con', 'response' => $message . ' ' . PHP_EOL . '00. Back ' . PHP_EOL . '000. Exit', 'map' => array((object) array('menu' => 'rtgsFormRecipientAccount')), 'type' => 'form');
        } else {
            $response = (object) array('id' => 'RecipientBranchFetcherRTGS', 'action' => 'con', 'response' => 'Sorry, there are currently no supported banks' . PHP_EOL . '00. Back ' . PHP_EOL . '000. Exit', 'map' => array((object) array('menu' => 'RecipientBanksFetcherEFT')), 'type' => 'form');
        }

        return $response;
    }

    public function rtgsConfirm() {
        $Accounts = explode(',', Cache::get($this->msisdn . 'ACCOUNTS'));
        $Selected = Cache::has($this->msisdn . 'RTGSAccounts') ? Cache::get($this->msisdn . 'RTGSAccounts') : "";
        $Index = $Selected - 1;
        $Account = $Accounts[$Index];
        $RTGSBanks = Cache::get($this->msisdn . 'RTGSBANKS');
        $RTGSBranches = explode('~', Cache::get($this->msisdn . 'RTGSBRANCHES'));
        $selectedIndex = Cache::get($this->msisdn . 'RecipientBanksFetcherRTGS');
        $Index = $selectedIndex - 1;
        if ($selectedIndex > $Index) {
            $Amount = Cache::get($this->msisdn . 'rtgsFormAmount');
            $ToAccount = Cache::get($this->msisdn . 'rtgsFormRecipientAccount');
            $RecipientName = str_replace("dddd", " ", Cache::get($this->msisdn . 'rtgsFormRecipientName'));
            $Narration = str_replace("dddd", " ", Cache::get($this->msisdn . 'rtgsFormNarration'));
            $Banks = explode("~", $RTGSBanks);
            $Bank = explode("|", $Banks[$Index]);
            $SelectedBranchIndex = Cache::get($this->msisdn . 'RecipientBranchFetcherRTGS');
            $Branch = $RTGSBranches[$SelectedBranchIndex - 1];

            //Charges
            $Charges = $this->Charges("RTGS", $Amount);
            $ChargeMessage = "";
            if ($Charges > 0) {
                $ChargeMessage = "Transaction charges Ksh. " . $Charges;
            } else {
                $ChargeMessage = "This is a free service";
            }

            $message = "Send " . $Amount . " Amount From " . $Account . " To " . $RecipientName . ", Account " . $ToAccount . ", Bank " . $Bank[1] . " ?\n " . $ChargeMessage . "\n Reply With:\n1. Accept\n2. Cancel";

            $response = (object) array('id' => 'rtgsConfirm', 'action' => 'con', 'response' => $message, 'map' => array((object) array('menu' => 'rtgsConfirmFinal')), 'type' => 'form');
        } else {
            $response = (object) array('id' => 'rtgsConfirm', 'action' => 'con', 'response' => 'Invalid bank selection. Please try again', 'map' => array((object) array('menu' => 'rtgsConfirmFinal')), 'type' => 'form');
        }
        return $response;
    }

    public function rtgsConfirmFinal() {
        $start_time = microtime(true);
        if (Cache::has($this->msisdn . 'rtgsConfirm') && Cache::get($this->msisdn . 'rtgsConfirm') == "1") {
            if (Cache::has($this->msisdn . 'rtgsConfirmFinal') && Cache::get($this->msisdn . 'rtgsConfirmFinal') != "") {
                $trx_start = microtime(true);
                $pin = Cache::get($this->msisdn . 'rtgsConfirmFinal');

                $Selected = Cache::has($this->msisdn . 'RTGSAccounts') ? Cache::get($this->msisdn . 'RTGSAccounts') : "";
                $Accounts = explode(',', Cache::get($this->msisdn . 'ACCOUNTS'));
                $CustomerID = Cache::get($this->msisdn . 'CUSTOMERID');
                $Index = $Selected - 1;
                $Account = $Accounts[$Index];
                $Amount = Cache::get($this->msisdn . 'rtgsFormAmount');
                $ToAccount = Cache::get($this->msisdn . 'rtgsFormRecipientAccount');
                $RecipientName = str_replace("dddd", " ", Cache::get($this->msisdn . 'rtgsFormRecipientName'));
                $Narration = str_replace("dddd", " ", Cache::get($this->msisdn . 'rtgsFormNarration'));
                $RTGSBanks = Cache::get($this->msisdn . 'RTGSBANKS');
                $selectedIndex = Cache::get($this->msisdn . 'RecipientBanksFetcherRTGS');
                $rtgsIndex = $selectedIndex - 1;
                $Banks = explode("~", $RTGSBanks);
                $Bank = explode("|", $Banks[$rtgsIndex]);
                $CustomerID = Cache::get($this->msisdn . 'CUSTOMERID');
                $Index = $Selected - 1;
                $Account = $Accounts[$Index];


                $RTGSBranches = explode('~', Cache::get($this->msisdn . 'RTGSBRANCHES'));
                $SelectedBranchIndex = Cache::get($this->msisdn . 'RecipientBranchFetcherRTGS');
                $Branch = explode("|", $RTGSBranches[$SelectedBranchIndex - 1]);

                $BankName = "FTB";
                $DataToSend = 'FORMID:B-:MERCHANTID:RTGS:BANKACCOUNTID:' . $Account . ':INFOFIELD1:' . $RecipientName . ':INFOFIELD2:' . $Bank[0] . ':INFOFIELD3:' . $Branch[0] . ':TOACCOUNT:' . $ToAccount . ':AMOUNT:' . $Amount . ':MESSAGE:' . $Narration . ':TMPIN:' . $pin . ':BANKNAME:' . $BankName . ':BANKID:' . $this->DefaultBankID . ':CUSTOMERID:' . $CustomerID . ':MOBILENUMBER:' . $this->msisdn . ':SHORTCODE:' . $this->shortcode . ':COUNTRY:' . $this->Country . ':TRXSOURCE:USSD:';
                $this->NewLog($this->msisdn, "RTGS REQUEST : " . $DataToSend);
                $ElmaResponse = $this->ElmaU($DataToSend);
                $this->NewLog($this->msisdn, "RTGS RESPONSE : " . strip_tags($ElmaResponse));
                $responseData = explode(':', strip_tags($ElmaResponse));

                $trx_end = microtime(true);
                Log::notice(Carbon::now() . " - " . $this->msisdn . "- RTGS TRANSACTION TIME : " . ($trx_start - $trx_end));

                $response = "";
                if ($responseData[1] == "000") {
                    Cache::forget($this->msisdn . 'rtgsFormAmount');
                    Cache::forget($this->msisdn . 'rtgsFormRecipientAccount');
                    $response = (object) array('id' => 'rtgsConfirmFinal', 'action' => 'end', 'response' => $responseData[3], 'map' => array((object) array('menu' => 'rtgsConfirmFinal')), 'type' => 'static');
                } else {
                    $response = (object) array('id' => 'rtgsConfirmFinal', 'action' => 'end', 'response' => $responseData[3], 'map' => array((object) array('menu' => 'rtgsConfirmFinal')), 'type' => 'static');
                }
                return $response;
            } else {
                $end_time = microtime(true);
                $this->NewLog($this->msisdn, "RTGS TRANSACTION TIME : " . ($start_time - $end_time));
                return (object) array('id' => 'rtgsConfirmFinal', 'action' => 'con', 'response' => 'Enter Mobile PIN', 'map' => array((object) array('menu' => 'rtgsConfirmFinal')), 'type' => 'form');
            }
        } else {
            $end_time = microtime(true);
            $this->NewLog($this->msisdn, "RTGS TRANSACTION TIME : " . ($start_time - $end_time));
            return (object) array('id' => 'rtgsConfirmFinal', 'action' => 'con', 'response' => 'Transaction request was canceled ' . PHP_EOL . '0. Home ' . PHP_EOL . '000. Exit', 'map' => array((object) array('menu' => 'groupsave')), 'type' => 'form');
        }
    }

    public function RecipientBanksFetcherEFT() {
        $trx_start = microtime(true);
        $CustomerID = Cache::get($this->msisdn . 'CUSTOMERID');
        $BankName = "FTB";

        $Selected = Cache::get($this->msisdn . 'EFTBanksFilter');
        $Filter = "";
        if ($Selected == "1") {
            $Filter = "A-B";
        } else if ($Selected == "2") {
            $Filter = "C";
        } else if ($Selected == "3") {
            $Filter = "D-E";
        } else if ($Selected == "4") {
            $Filter = "F";
        } else if ($Selected == "5") {
            $Filter = "G-H";
        } else if ($Selected == "6") {
            $Filter = "I-M";
        } else if ($Selected == "7") {
            $Filter = "N-S";
        } else if ($Selected == "8") {
            $Filter = "T-Z";
        }

        //$DataToSend = 'FORMID:O-GetCommercialBanksUSSD:CUSTOMERID:'.$CustomerID.':MOBILENUMBER:'.$this->msisdn.':BANKNAME:'.$BankName.':BANKID:'.$this->DefaultBankID.':COUNTRY:'.$this->Country.':TRXSOURCE:USSD:';
        //$DataToSend = 'FORMID:O-GetCommercialBankIDAndName:CUSTOMERID:'.$CustomerID.':MOBILENUMBER:'.$this->msisdn.':SHORTCODE:'.$this->shortcode.':BANKNAME:'.$BankName.':BANKID:'.$this->DefaultBankID.':COUNTRY:'.$this->Country.':TRXSOURCE:USSD:';
        $DataToSend = 'FORMID:O-GetCommercialBankWithFilter:FILTER:' . $Filter . ':CUSTOMERID:' . $CustomerID . ':MOBILENUMBER:' . $this->msisdn . ':SHORTCODE:' . $this->shortcode . ':BANKNAME:' . $BankName . ':BANKID:' . $this->DefaultBankID . ':COUNTRY:' . $this->Country . ':TRXSOURCE:USSD:';
        $this->NewLog($this->msisdn, "TRANSFER BANKS REQUEST : " . $DataToSend);
        $ElmaResponse = $this->ElmaU($DataToSend);
        $this->NewLog($this->msisdn, "TRANSFER BANKS RESPONSE : " . strip_tags($ElmaResponse));
        $responseData = explode(':', strip_tags($ElmaResponse));

        $trx_end = microtime(true);
        Log::notice(Carbon::now() . " - " . $this->msisdn . "- TRANSFER BANKS TRANSACTION TIME : " . ($trx_start - $trx_end));
        Cache::add($this->msisdn . 'EFTBANKS', $responseData[3], 2);
        if (count($responseData) > 0) {
            $message = "Select Bank to transfer to: \n";
            $num = 1;
            $banks = explode("~", $responseData[3]);
            foreach ($banks as $bank) {
                // code...
                $message .= $num . " " . explode("|", $bank)[1] . "\n";
                $num++;
            }
            $response = (object) array('id' => 'RecipientBanksFetcherEFT', 'action' => 'con', 'response' => $message . ' ' . PHP_EOL . '00. Back ' . PHP_EOL . '000. Exit', 'map' => array((object) array('menu' => 'EFTBranchFilter')), 'type' => 'form');
        } else {
            $response = (object) array('id' => 'RecipientBanksFetcherEFT', 'action' => 'con', 'response' => 'Sorry, there are currently no supported banks' . PHP_EOL . '00. Back ' . PHP_EOL . '000. Exit', 'map' => array((object) array('menu' => 'RecipientBanksFetcherEFT')), 'type' => 'form');
        }

        return $response;
    }

    public function RecipientBranchFetcherEFT() {
        $trx_start = microtime(true);
        $CustomerID = Cache::get($this->msisdn . 'CUSTOMERID');
        $BankName = "FTB";

        $Selected = Cache::get($this->msisdn . 'EFTBranchFilter');
        $Filter = "";
        if ($Selected == "1") {
            $Filter = "A-B";
        } else if ($Selected == "2") {
            $Filter = "C";
        } else if ($Selected == "3") {
            $Filter = "D-E";
        } else if ($Selected == "4") {
            $Filter = "F";
        } else if ($Selected == "5") {
            $Filter = "G-H";
        } else if ($Selected == "6") {
            $Filter = "I-M";
        } else if ($Selected == "7") {
            $Filter = "N-S";
        } else if ($Selected == "8") {
            $Filter = "T-Z";
        }

        $RTGSBanks = explode("~", Cache::get($this->msisdn . 'EFTBANKS'));
        $SelectedIndex = Cache::get($this->msisdn . 'RecipientBanksFetcherEFT');
        $SelectedBank = explode("|", $RTGSBanks[$SelectedIndex - 1]);

        $DataToSend = 'FORMID:O-GetCommercialBankBranchWithFilter:BANKFILTER:' . $SelectedBank[0] . ':FILTER:' . $Filter . ':CUSTOMERID:' . $CustomerID . ':MOBILENUMBER:' . $this->msisdn . ':SHORTCODE:' . $this->shortcode . ':BANKNAME:' . $BankName . ':BANKID:' . $this->DefaultBankID . ':COUNTRY:' . $this->Country . ':TRXSOURCE:USSD:';
        $this->NewLog($this->msisdn, "EFT BANKS REQUEST : " . $DataToSend);
        $ElmaResponse = $this->ElmaU($DataToSend);
        $this->NewLog($this->msisdn, "EFT BANKS RESPONSE : " . strip_tags($ElmaResponse));
        $responseData = explode(':', strip_tags($ElmaResponse));

        $trx_end = microtime(true);
        Log::notice(Carbon::now() . " - " . $this->msisdn . "- EFT BANKS TRANSACTION TIME : " . ($trx_start - $trx_end));
        Cache::add($this->msisdn . 'EFTBRANCHES', $responseData[3], 2);
        if (count($responseData) > 0) {
            $message = "Select Branch: \n";
            $num = 1;
            $branches = explode("~", $responseData[3]);
            foreach ($branches as $branche) {
                // code...
                $message .= $num . " " . explode("|", $branche)[1] . "\n";
                $num++;
            }
            $response = (object) array('id' => 'RecipientBranchFetcherEFT', 'action' => 'con', 'response' => $message . ' ' . PHP_EOL . '00. Back ' . PHP_EOL . '000. Exit', 'map' => array((object) array('menu' => 'eftFormRecipientAccount')), 'type' => 'form');
        } else {
            $response = (object) array('id' => 'RecipientBranchFetcherEFT', 'action' => 'con', 'response' => 'Sorry, there are currently no supported banks' . PHP_EOL . '00. Back ' . PHP_EOL . '000. Exit', 'map' => array((object) array('menu' => 'RecipientBranchFetcherEFT')), 'type' => 'form');
        }

        return $response;
    }

    public function eftConfirm() {
        $Accounts = explode(',', Cache::get($this->msisdn . 'ACCOUNTS'));
        $Selected = Cache::has($this->msisdn . 'FTAccounts') ? Cache::get($this->msisdn . 'FTAccounts') : "";
        $Index = $Selected - 1;
        $Account = $Accounts[$Index];
        $Amount = Cache::get($this->msisdn . 'InterBankAmount');

        $ToAccount = "";
        $ToAccount = Cache::get($this->msisdn . 'InterBankAccount');

        $Amount = Cache::get($this->msisdn . 'InterBankAmount');

        $message = "Send " . $Amount . " Amount To " . $ToAccount . "\n Reply With:\n1. Accept\n2. Cancel";

        $response = (object) array('id' => 'eftConfirm', 'action' => 'con', 'response' => $message, 'map' => array((object) array('menu' => 'eftConfirmFinal')), 'type' => 'form');

        return $response;
    }

    public function eftConfirmFinal() {
        $start_time = microtime(true);
        if (Cache::has($this->msisdn . 'eftConfirm') && Cache::get($this->msisdn . 'eftConfirm') == "1") {
            if (Cache::has($this->msisdn . 'eftConfirmFinal') && Cache::get($this->msisdn . 'eftConfirmFinal') != "") {
                $trx_start = microtime(true);
                $pin = Cache::get($this->msisdn . 'eftConfirmFinal');

                $Selected = Cache::has($this->msisdn . 'FTAccounts') ? Cache::get($this->msisdn . 'FTAccounts') : "";
                $Accounts = explode(',', Cache::get($this->msisdn . 'ACCOUNTS'));
                $CustomerID = Cache::get($this->msisdn . 'CUSTOMERID');
                $Index = $Selected - 1;
                $Account = $Accounts[$Index];

                $ToAccount = "";
                $ToAccount = Cache::get($this->msisdn . 'InterBankAccount');
                $Amount = Cache::get($this->msisdn . 'InterBankAmount');

                //$RecipientName = str_replace("----", " ", Cache::get($this->msisdn.'iftFormRecipientName'));
                //$Narration = str_replace("----", " ", Cache::get($this->msisdn.'iftFormNarration'));
                $Narration = "Qw";
                $CustomerID = Cache::get($this->msisdn . 'CUSTOMERID');
                $Index = $Selected - 1;
                $Account = $Accounts[$Index];

                $BankName = $this->BankName;
                $DataToSend = 'FORMID:B-:MERCHANTID:INTERBANK:BANKACCOUNTID:' . $Account . ':TOBANK:16:TOACCOUNT:' . $ToAccount . ':AMOUNT:' . $Amount . ':MESSAGE:' . $Narration . ':TMPIN:' . $pin . ':SHORTCODE:' . $this->shortcode . ':CUSTOMERID:' . $CustomerID . ':MOBILENUMBER:' . $this->msisdn . ':BANKID:' . $this->DefaultBankID . ':COUNTRY:' . $this->Country . ':BANKNAME:' . $this->BankName . ':UNIQUEID:' . $this->guid() . ':TRXSOURCE:USSD:';
                $this->NewLog($this->msisdn, "EFT REQUEST : " . $DataToSend);
                $ElmaResponse = $this->ElmaU($DataToSend);
                $this->NewLog($this->msisdn, "EFT RESPONSE : " . strip_tags($ElmaResponse));
                $responseData = explode(':', strip_tags($ElmaResponse));

                $trx_end = microtime(true);
                Log::notice(Carbon::now() . " - " . $this->msisdn . "- EFT TRANSACTION TIME : " . ($trx_start - $trx_end));

                $response = "";
                if ($responseData[1] == "000") {
                    $response = (object) array('id' => 'eftConfirmFinal', 'action' => 'end', 'response' => $responseData[3], 'map' => array((object) array('menu' => 'eftConfirmFinal')), 'type' => 'static');
                } else {
                    $response = (object) array('id' => 'eftConfirmFinal', 'action' => 'end', 'response' => $responseData[3], 'map' => array((object) array('menu' => 'eftConfirmFinal')), 'type' => 'static');
                }
                Cache::forget($this->msisdn . 'InterBankAccount');
                Cache::forget($this->msisdn . 'InterBankAmount');
                return $response;
            } else {
                $end_time = microtime(true);
                $this->NewLog($this->msisdn, "EFT TRANSACTION TIME : " . ($start_time - $end_time));
                return (object) array('id' => 'eftConfirmFinal', 'action' => 'con', 'response' => 'Enter your pin to complete transaction', 'map' => array((object) array('menu' => 'eftConfirmFinal')), 'type' => 'form');
            }
        } else {
            Cache::forget($this->msisdn . 'InterBankAccount');
            Cache::forget($this->msisdn . 'InterBankAmount');
            $end_time = microtime(true);
            $this->NewLog($this->msisdn, "EFT TRANSACTION TIME : " . ($start_time - $end_time));
            return (object) array('id' => 'eftConfirmFinal', 'action' => 'con', 'response' => 'Transaction request was canceled ' . PHP_EOL . '0. Home ' . PHP_EOL . '000. Exit', 'map' => array((object) array('menu' => 'groupsave')), 'type' => 'form');
        }
    }

    public function FTAccounts() {
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

            $response = (object) array('id' => 'FTAccounts', 'action' => 'con', 'response' => $message, 'map' => array((object) array('menu' => 'fundst')), 'type' => 'form');
        } else {
            $response = (object) array('action' => 'con', 'response' => 'You dont have any accounts', 'map' => array((object) array('menu' => 'home')), 'type' => 'form');
        }

        return $response;
    }

    public function validateFTAccount() {
        $ToAccount = Cache::get($this->msisdn . 'iftFormAccount');
        $CustomerID = Cache::get($this->msisdn . 'CUSTOMERID');
        $DataToSend = 'FORMID:M-:MERCHANTID:VALIDATEACCOUNT:ACCOUNTID:' . $ToAccount . ':CUSTOMERID:' . $CustomerID . ':MOBILENUMBER:' . $this->msisdn . ':BANKNAME:' . $this->BankName . ':SHORTCODE:' . $this->shortcode . ':BANKID:' . $this->DefaultBankID . ':COUNTRY:' . $this->Country . ':ACTION:GETNAME:UNIQUEID:' . $this->guid() . ':TRXSOURCE:USSD:';
        $this->logErr($this->msisdn, "VALIDATE FT ACCOUNT REQUEST : " . $DataToSend);
        $ElmaResponse = $this->ElmaU($DataToSend);
        $responseData = explode(':', strip_tags($ElmaResponse));
        $this->logErr($this->msisdn, "VALIDATE FT ACCOUNT RESPONSE : " . strip_tags($ElmaResponse));

        if (count($responseData) > 1) {
            if ($responseData[1] == "000" || $responseData[1] == "OK") {
                $Data = $responseData[3];
                $response = (object) array('id' => 'validateFTAccount', 'action' => 'con', 'response' => "The account belongs to POC POC\n1.Accept\n2.Back", 'map' => array((object) array('menu' => 'iftFormAmountDirect')), 'type' => 'form');
            } else {
                $response = (object) array('id' => 'iftFormAccount', 'action' => 'con', 'response' => "Invalid Account. Please Enter a Correct Account No::\n00.Back\n0.Main Menu", 'map' => array((object) array('menu' => 'validateFTAccount')), 'type' => 'form');
            }
        } else {
            Cache::add($this->msisdn . 'CUSTOMERNAME', $responseData[0], 1);
            $response = (object) array('id' => 'validateFTAccount', 'action' => 'con', 'response' => "Send to " . $responseData[0] . "?\n1.Accept\n2.Back", 'map' => array((object) array('menu' => 'iftFormAmountDirect')), 'type' => 'form');
        }


        return $response;
    }

    public function iftConfirm() {
        $Accounts = explode(',', Cache::get($this->msisdn . 'ACCOUNTS'));
        $Selected = Cache::has($this->msisdn . 'FTAccounts') ? Cache::get($this->msisdn . 'FTAccounts') : "";
        $Index = $Selected - 1;
        $Account = $Accounts[$Index];
        $Amount = Cache::get($this->msisdn . 'iftFormAmountDirect');

        $ToAccount = "";
        $ToAccount = Cache::get($this->msisdn . 'iftFormAccount');

        $Amount = Cache::get($this->msisdn . 'iftFormAmountDirect');
        $CustomerName = Cache::get($this->msisdn . 'CUSTOMERNAME');

        $message = "Send " . $Amount . " Amount To POC POC\n Reply With:\n1. Accept\n2. Cancel";

        $response = (object) array('id' => 'iftConfirm', 'action' => 'con', 'response' => $message, 'map' => array((object) array('menu' => 'iftConfirmFinal')), 'type' => 'form');

        return $response;
    }

    public function iftConfirmFinal() {
        $start_time = microtime(true);
        if (Cache::has($this->msisdn . 'iftConfirm') && Cache::get($this->msisdn . 'iftConfirm') == "1") {
            $Amount = Cache::get($this->msisdn . 'iftFormAmountDirect');
            $response = "You have successfully transfered KES " . $Amount . " To POC POC, Thank you for Banking with us";
            return $response;
        } else {

            return (object) array('id' => 'iftConfirmFinal', 'action' => 'con', 'response' => 'Transaction request was canceled ' . PHP_EOL . '0. Home ' . PHP_EOL . '000. Exit', 'map' => array((object) array('menu' => 'groupsave')), 'type' => 'form');
        }
    }

    public function AgentAccounts() {
        $all_accounts = Cache::get($this->msisdn . 'ACCOUNTS');
        $Accounts = explode(',', $all_accounts);

        if (count($Accounts) > 0) {
            $message = "Select Bank\n";

            $num = 1;
            foreach ($Accounts as $Account) {
                if ($Account != "") {
                    $message .= $num . ". HFB/" . $Account . "\n";
                    $num++;
                }
            }
            $message .= "00. Home \n 000. Exit";

            $response = (object) array('id' => 'AgentAccounts', 'action' => 'con', 'response' => $message, 'map' => array((object) array('menu' => 'AgentWithdraw')), 'type' => 'form');
        } else {
            $response = (object) array('action' => 'con', 'response' => 'You dont have any accounts', 'map' => array((object) array('menu' => 'home')), 'type' => 'form');
        }

        return $response;
    }

    public function AgentWithdraw() {

        $widthdrawAmount = Cache::get($this->msisdn . 'AgencyWithdraw');

        $Selected = Cache::get($this->msisdn . 'AgentAccounts');
        $Accounts = explode(',', Cache::get($this->msisdn . 'ACCOUNTS'));
        $CustomerID = Cache::get($this->msisdn . 'CUSTOMERID');
        $Index = $Selected - 1;
        $Account = $Accounts[$Index];

        $response = "";
        $response = "Agency\nWithdraw " . $widthdrawAmount . " at Agent location Reply with:\n1. Accept\n2. Cancel \n0. Home";

        return (object) array('id' => 'AgentWithdraw', 'action' => 'con', 'response' => $response, 'map' => array((object) array('menu' => 'AgentWithdrawConfirm')), 'type' => 'form');
    }

    public function AgentWithdrawConfirm() {
        $start_time = microtime(true);
        if (Cache::has($this->msisdn . 'AgentWithdraw') && Cache::get($this->msisdn . 'AgentWithdraw') == "1") {
            if (Cache::has($this->msisdn . 'AgentWithdrawConfirm') && Cache::get($this->msisdn . 'AgentWithdrawConfirm') != "") {

                $trx_start = microtime(true);
                $pin = Cache::get($this->msisdn . 'AgentWithdrawConfirm');

                $Selected = Cache::get($this->msisdn . 'AgentAccounts');
                $Accounts = explode(',', Cache::get($this->msisdn . 'ACCOUNTS'));
                $CustomerID = Cache::get($this->msisdn . 'CUSTOMERID');
                $Index = $Selected - 1;
                $Account = $Accounts[$Index];

                $widthdrawAmount = Cache::get($this->msisdn . 'AgencyWithdraw');

                $CustomerID = Cache::get($this->msisdn . 'CUSTOMERID');
                $BankName = $this->BankName;
                $DataToSend = 'FORMID:B-:MERCHANTID:AGENTWITHDRAWAL:AMOUNT:' . $widthdrawAmount . ':TMPIN:' . $pin . ':BANKNAME:' . $this->BankName . ':BANKACCOUNTID:' . $Account . ':ACTION:PAYBILL:CUSTOMERID:' . $CustomerID . ':MOBILENUMBER:' . $this->msisdn . ':SHORTCODE:' . $this->shortcode . ':BANKID:' . $this->DefaultBankID . ':COUNTRY:' . $this->Country . ':QUICKPAY:NO:UNIQUEID:' . $this->guid() . ':';
                $this->logErr($this->msisdn, "AGENCY WITHDRAW REQUEST : " . $DataToSend);
                $ElmaResponse = $this->ElmaU($DataToSend);
                $this->logErr($this->msisdn, "AGENCY WITHDRAW RESPONSE : " . strip_tags($ElmaResponse));
                $responseData = explode(':', strip_tags($ElmaResponse));

                $trx_end = microtime(true);
                Log::notice(Carbon::now() . " - " . $this->msisdn . "- AGENCY TRANSACTION TIME : " . ($trx_start - $trx_end));

                $response = "";
                if ($responseData[1] == "000") {
                    $response = (object) array('action' => 'end', 'response' => $responseData[3], 'map' => array((object) array('menu' => 'AgentWithdrawConfirm')), 'type' => 'static');
                } else if ($responseData[1] == "091") {
                    $response = (object) array('action' => 'end', 'response' => $responseData[3], 'map' => array((object) array('menu' => 'groupsave')), 'type' => 'static');
                } else {
                    $response = (object) array('action' => 'end', 'response' => $responseData[3], 'map' => array((object) array('menu' => 'groupsave')), 'type' => 'static');
                }
                return $response;
            } else {
                $end_time = microtime(true);
                $this->logErr($this->msisdn, "TRANSACTION TIME : " . ($start_time - $end_time));
                return (object) array('id' => 'AgentWithdrawConfirm', 'action' => 'con', 'response' => 'Enter your pin to Complete Transaction', 'map' => array((object) array('menu' => 'AgentWithdrawConfirm')), 'type' => 'form');
            }
        } else {
            $end_time = microtime(true);
            Cache::forget($this->msisdn . 'AgencyWithdraw');
            $this->logErr($this->msisdn, "TRANSACTION TIME : " . ($start_time - $end_time));
            return (object) array('id' => 'AgentWithdrawConfirm', 'action' => 'con', 'response' => 'Transaction request was cancelled. ' . PHP_EOL . '00. Home ' . PHP_EOL . '000. Exit', 'map' => array((object) array('menu' => 'AgentWithdrawConfirm')), 'type' => 'form');
        }
    }

    public function pinChangeConfirmNew() {
        //FORMID:O-ChangeLPIN-BANKID-{0}-OPIN-{1}-NPIN-{2}-CPIN-{3}:SHORTCODE:{4}:CUSTOMERID:{5}:UNIQUEID:{6}:TRXSOURCE:USSD:
        $oldPin = Cache::get($this->msisdn . 'changePin');
        $newPin1 = Cache::get($this->msisdn . 'newPin1');
        $newPin2 = Cache::get($this->msisdn . 'newPin2');

        $CustomerID = Cache::get($this->msisdn . 'CUSTOMERID');
        $BankName = "FTB";

        $response = "";
        if ($newPin2 == $newPin1) {
            $DataToSend = 'FORMID:PINCHANGE:BANKID:' . $this->DefaultBankID . ':OLDPIN:' . $oldPin . ':NEWPIN:' . $newPin1 . ':PINTYPE:CHANGEPIN:SHORTCODE:' . $this->shortcode . ':CUSTOMERID:' . $CustomerID . ':MOBILENUMBER:' . $this->msisdn . ':COUNTRY:' . $this->Country . ':UNIQUEID:' . $this->guid() . ':TRXSOURCE:USSD:';
            //$DataToSend = 'FORMID:O-ChangeLPIN:BANKID:'.$this->DefaultBankID.':OPIN:'.$oldPin.':NPIN:'.$newPin1.':CPIN:'.$newPin2.':BANKID:'.$this->DefaultBankID.':BANKNAME:'.$BankName.':COUNTRY:'.$this->Country.':SHORTCODE:669:CUSTOMERID:'.$CustomerID.':MOBILENUMBER:'.$this->msisdn.':UNIQUEID:'.$this->guid().':TRXSOURCE:USSD:EX11021969:YES:';
            $this->logErr($this->msisdn, "PIN CHANGE REQUEST : " . $DataToSend);
            $ElmaResponse = $this->ElmaU($DataToSend);
            $this->logErr($this->msisdn, "PIN CHANGE RESPONSE : " . strip_tags($ElmaResponse));
            $responseData = explode(':', strip_tags($ElmaResponse));

            if ($responseData != "") {
                if ($responseData[1] == "000") {
                    $response = (object) array('id' => 'pinChangeConfirm', 'action' => 'end', 'response' => $responseData[3], 'map' => array((object) array('menu' => 'pinChangeConfirm')), 'type' => 'static');
                } else {
                    $response = (object) array('id' => 'pinChangeConfirm', 'action' => 'con', 'response' => $responseData[3], 'map' => array((object) array('menu' => 'pinChangeConfirm')), 'type' => 'static');
                }
            } else {
                $response = (object) array('id' => 'pinChangeConfirm', 'action' => 'end', 'response' => "Sorry, there was a problem processing your request. Please try again", 'map' => array((object) array('menu' => 'pinChangeConfirm')), 'type' => 'static');
            }
        } else {
            $response = (object) array('id' => 'pinChangeConfirm', 'action' => 'con', 'response' => "Sorry, the new PINs do not match. Please try again", 'map' => array((object) array('menu' => 'pinChangeConfirm')), 'type' => 'static');
        }
        return $response;
    }

    public function mpinChangeConfirmNew() {
        //FORMID:O-ChangeLPIN-BANKID-{0}-OPIN-{1}-NPIN-{2}-CPIN-{3}:SHORTCODE:{4}:CUSTOMERID:{5}:UNIQUEID:{6}:TRXSOURCE:USSD:
        $oldPin = Cache::get($this->msisdn . 'changeMPin');
        $newPin1 = Cache::get($this->msisdn . 'newMPin1');
        $newPin2 = Cache::get($this->msisdn . 'newMPin2');

        $CustomerID = Cache::get($this->msisdn . 'CUSTOMERID');
        $BankName = "FTB";

        $response = "";
        if ($newPin2 == $newPin1) {
            $DataToSend = 'FORMID:PINCHANGE:BANKID:' . $this->DefaultBankID . ':OLDPIN:' . $oldPin . ':NEWPIN:' . $newPin1 . ':PINTYPE:MPIN:SHORTCODE:' . $this->shortcode . ':CUSTOMERID:' . $CustomerID . ':MOBILENUMBER:' . $this->msisdn . ':COUNTRY:' . $this->Country . ':UNIQUEID:' . $this->guid() . ':TRXSOURCE:USSD:';
            //$DataToSend = 'FORMID:O-ChangeLPIN:BANKID:'.$this->DefaultBankID.':OPIN:'.$oldPin.':NPIN:'.$newPin1.':CPIN:'.$newPin2.':BANKID:'.$this->DefaultBankID.':BANKNAME:'.$BankName.':COUNTRY:'.$this->Country.':SHORTCODE:669:CUSTOMERID:'.$CustomerID.':MOBILENUMBER:'.$this->msisdn.':UNIQUEID:'.$this->guid().':TRXSOURCE:USSD:EX11021969:YES:';
            $this->logErr($this->msisdn, "PIN CHANGE REQUEST : " . $DataToSend);
            $ElmaResponse = $this->ElmaU($DataToSend);
            $this->logErr($this->msisdn, "PIN CHANGE RESPONSE : " . strip_tags($ElmaResponse));
            $responseData = explode(':', strip_tags($ElmaResponse));

            if ($responseData != "") {
                if ($responseData[1] == "000") {
                    $response = (object) array('id' => 'mpinChangeConfirmNew', 'action' => 'end', 'response' => $responseData[3], 'map' => array((object) array('menu' => 'mpinChangeConfirmNew')), 'type' => 'static');
                } else {
                    $response = (object) array('id' => 'mpinChangeConfirmNew', 'action' => 'con', 'response' => $responseData[3], 'map' => array((object) array('menu' => 'mpinChangeConfirmNew')), 'type' => 'static');
                }
            } else {
                $response = (object) array('id' => 'mpinChangeConfirmNew', 'action' => 'end', 'response' => "Sorry, there was a problem processing your request. Please try again", 'map' => array((object) array('menu' => 'mpinChangeConfirmNew')), 'type' => 'static');
            }
        } else {
            $response = (object) array('id' => 'mpinChangeConfirmNew', 'action' => 'con', 'response' => "Sorry, the new PINs do not match. Please try again", 'map' => array((object) array('menu' => 'mpinChangeConfirmNew')), 'type' => 'static');
        }
        return $response;
    }

    public function forcedPinChangeConfirm() {
        //FORMID:O-ChangeLPIN-BANKID-{0}-OPIN-{1}-NPIN-{2}-CPIN-{3}:SHORTCODE:{4}:CUSTOMERID:{5}:UNIQUEID:{6}:TRXSOURCE:USSD:
        $oldPin = Cache::get($this->msisdn . 'pin');
        $newPin1 = Cache::get($this->msisdn . 'newPin11');
        $newPin2 = Cache::get($this->msisdn . 'newPin22');

        $CustomerID = Cache::get($this->msisdn . 'CUSTOMERID');
        $BankName = "FTB";

        $response = "";
        if ($newPin2 == $newPin1) {
            //$DataToSend = 'FORMID:PINCHANGE:BANKID:'.$this->DefaultBankID.':OLDLPIN:'.$oldPin.':NEWLPIN:'.$newPin1.':PINTYPE:PIN:SHORTCODE:'.$this->shortcode.':CUSTOMERID:'.$CustomerID.':MOBILENUMBER:'.$this->msisdn.':COUNTRY:'.$this->Country.':UNIQUEID:'.$this->guid().':TRXSOURCE:USSD:';
            $DataToSend = 'FORMID:PINCHANGE:BANKID:' . $this->DefaultBankID . ':OLDPIN:' . $oldPin . ':NEWPIN:' . $newPin1 . ':PINTYPE:CHANGEPIN:SHORTCODE:' . $this->shortcode . ':CUSTOMERID:' . $CustomerID . ':MOBILENUMBER:' . $this->msisdn . ':COUNTRY:' . $this->Country . ':UNIQUEID:' . $this->guid() . ':TRXSOURCE:USSD:';
            //$DataToSend = 'FORMID:O-ChangeLPIN:BANKID:'.$this->DefaultBankID.':OPIN:'.$oldPin.':NPIN:'.$newPin1.':CPIN:'.$newPin2.':BANKID:'.$this->DefaultBankID.':BANKNAME:'.$BankName.':COUNTRY:'.$this->Country.':SHORTCODE:669:CUSTOMERID:'.$CustomerID.':MOBILENUMBER:'.$this->msisdn.':UNIQUEID:'.$this->guid().':TRXSOURCE:USSD:EX11021969:YES:';
            $this->logErr($this->msisdn, "PIN CHANGE REQUEST : " . $DataToSend);
            $ElmaResponse = $this->ElmaU($DataToSend);
            $this->logErr($this->msisdn, "PIN CHANGE RESPONSE : " . strip_tags($ElmaResponse));
            $responseData = explode(':', strip_tags($ElmaResponse));

            if ($responseData != "") {
                if ($responseData[1] == "000") {
                    $response = (object) array('id' => 'pinChangeConfirm', 'action' => 'end', 'response' => $responseData[3], 'map' => array((object) array('menu' => 'forcedPinChangeConfirm')), 'type' => 'static');
                } else {
                    $response = (object) array('id' => 'pinChangeConfirm', 'action' => 'end', 'response' => $responseData[3], 'map' => array((object) array('menu' => 'forcedPinChangeConfirm')), 'type' => 'static');
                }
            } else {
                $response = (object) array('id' => 'pinChangeConfirm', 'action' => 'end', 'response' => "Sorry, there was a problem processing your request. Please try again", 'map' => array((object) array('menu' => 'forcedPinChangeConfirm')), 'type' => 'static');
            }
        } else {
            $response = (object) array('id' => 'pinChangeConfirm', 'action' => 'con', 'response' => "Sorry, the new PINs do not match. Please try again", 'map' => array((object) array('menu' => 'forcedPinChangeConfirm')), 'type' => 'static');
        }
        return $response;
    }

    public function LoanProducts() {
        $BankAccountID = Cache::get($this->msisdn . 'ACCOUNTS');
        $CustomerID = Cache::get($this->msisdn . 'CUSTOMERID');
        $LoanAmount = Cache::get($this->msisdn . 'loansamt');
        $LoanLimit = Cache::get($this->msisdn . 'LOANLIMIT');
        $Assessment = Cache::get($this->msisdn . 'ASSESSMENT');
        $accept = Cache::has($this->msisdn . 'LoanProducts') ? Cache::get($this->msisdn . 'LoanProducts') : "";

        if ($accept == "") {
            /* $DataToSend = 'FORMID:M-:MERCHANTID:INDIVIDUALACTIVITY:INFOFIELD1:INDIVIDUALACTIVITY:INFOFIELD2:SAFARICOM:INFOFIELD3:VALIDATEAMOUNT:INFOFIELD4:LOANREQUEST:INFOFIELD5:'. $LoanAmount .':INFOFIELD9:'. $this->msisdn .':METERNUMBER:'. $BankAccountID .':_WEBSERVICE_CALL_:MOBILELENDING:BANK:BARCLAYS:CUSTOMERID:'. $CustomerID .':ACCOUNTID:'.$BankAccountID.':BANKID:'. $this->DefaultBankID .':ACTION:GETNAME:COUNTRY:'. $this->Country .':';
              $this->logErr($this->msisdn, "LOAN PRODUCTS  REQUEST : ". $DataToSend );
              $ElmaResponse = $this->ElmaU($DataToSend);
              $responseData = explode(':', strip_tags($ElmaResponse));
              $this->logErr($this->msisdn, "LOAN PRODUCTS  RESPONSE : ". strip_tags($ElmaResponse)); */

            //$response = "";
            //if($responseData[1] == "000"){
            $response = (object) array('id' => 'LoanProducts', 'action' => 'con', 'response' => "Confirm loan application of\nKshs. " . $LoanAmount . " for  30days?\nApplicable interest of 1.083%\nand facility fee of 5 percent.\nReply with:\n1. Accept\n2. Cancel", 'map' => array((object) array('menu' => 'LoanProducts')), 'type' => 'form');
            /* }else{
              $response = (object) array('id' => 'LoanProducts', 'action' => 'end', 'response' => $responseData[3], 'map' => array( (object) array('menu' => 'LoanProducts')), 'type' => 'static');
              } */

            return $response;
        } else if ($accept == "1") {
            $DataToSend = 'FORMID:M-:MERCHANTID:INDIVIDUALACTIVITY:INFOFIELD1:INDIVIDUALACTIVITY:INFOFIELD2:SAFARICOM:INFOFIELD3:LOAN:INFOFIELD4:' . $LoanLimit . ':INFOFIELD5:1:INFOFIELD6:' . $LoanAmount . ':INFOFIELD9:' . $this->msisdn . ':INFOFIELD7:' . $Assessment . ':INFOFIELD8:New:AMOUNT:' . $LoanAmount . ':METERNUMBER::_WEBSERVICE_CALL_:MOBILELENDING:BANK:BARCLAYS:CUSTOMERID:' . $CustomerID . ':ACCOUNTID:' . $BankAccountID . ':BANKID:' . $this->DefaultBankID . ':ACTION:GETNAME:COUNTRY:' . $this->Country . ':';
            $ElmaResponse = $this->ElmaU($DataToSend);
            $responseData = explode(':', $ElmaResponse);
            $this->logErr($this->msisdn, "GET LOAN  REQUEST : " . $DataToSend);

            $response = (object) array('id' => 'LoanProducts', 'action' => 'end', 'response' => 'Your Loan Request has been submitted.' . PHP_EOL . 'We will Notify you shortly.' . PHP_EOL . '0.Main Menu', 'map' => array((object) array('menu' => 'LoanProducts')), 'type' => 'static');

            return $response;
        }
    }

    public function ElmaU($DataToSend) {
        //$ch = curl_init();
        $ch = curl_init();
        //curl_setopt($ch, CURLOPT_URL, 'http://172.17.20.34:23000/MobileMallUSSD_Q1/MobileMall.asmx/U?b='.urlencode($DataToSend));
        //curl_setopt($ch, CURLOPT_URL, 'http://172.17.20.34:23000/MobileMallUSSD/MobileMall.asmx/U?b='.urlencode($DataToSend));
        curl_setopt($ch, CURLOPT_URL, 'http://172.17.20.20:23000/MobileMallUSSD_UG/MobileMall.asmx/U?b=' . urlencode($DataToSend));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);

        curl_close($ch);

        return $result;
    }

    public function Logger($msisdn, $log) {
        $today = Carbon::parse(Carbon::today('Africa/Nairobi'))->format('Y-m-d');
        $myfile = fopen("/var/log/Barclays/" . $today . "/" . $msisdn . ".log", "w") or die("Unable to open file!");
        $txt = $log . "\n";
        fwrite($myfile, $txt);
        fclose($myfile);
    }

    function logErr($msisdn, $log) {
        $today = Carbon::parse(Carbon::today('Africa/Nairobi'))->format('Y-m-d');
        $time = Carbon::now('Africa/Nairobi');
        $log_filename = $_SERVER['DOCUMENT_ROOT'] . "/log/HFB/" . $today;
        //$log_filename = "/home/MobileMall/ussdLogs/UGANDATEST/HFB/".$today;
        if (!file_exists($log_filename)) {
            // create directory/folder uploads.
            mkdir($log_filename, 0777, true);
        }
        $log_file_data = $log_filename . '/' . $msisdn . '.log';
        file_put_contents($log_file_data, $time . " - " . $log . "\n", FILE_APPEND);
    }

    public function NewLog($MobileNumber, $LogText) {
        $url = "http://172.17.20.42:8099/api/v1/writeLog";
        $postData = array();
        //String contry,String application,String identification,String action,String text
        $postData["country"] = env('COUNTRY');
        $postData["application"] = env('BANK');
        $postData["identification"] = $MobileNumber;
        $postData["action"] = "";
        $postData["text"] = $LogText;

        $ch = curl_init();
        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        //curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, $postData);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //execute post
        $result = curl_exec($ch);
        //close connection
        curl_close($ch);

        Log::notice("LOG RESULT : " . $result);
    }

    function logReg($msisdn, $log) {
        $today = Carbon::parse(Carbon::today('Africa/Nairobi'))->format('Y-m-d');
        $time = Carbon::now('Africa/Nairobi');
        $log_filename = $_SERVER['DOCUMENT_ROOT'] . "/log/Barclays/Registration/" . $today;
        if (!file_exists($log_filename)) {
            // create directory/folder uploads.
            mkdir($log_filename, 0777, true);
        }
        $log_file_data = $log_filename . '/' . $msisdn . '.log';
        file_put_contents($log_file_data, $time . " - " . $log . "\n", FILE_APPEND);
    }

    public function TimizaRegistrationValidation() {
        $postData = "{meterDetails:{'UniqueID':'" . $this->guid() . "','MobileNumber':'" . $this->msisdn . "','BankID':'03','CustomerID':'25400003','Request':'','Response':'','StanNumber':'0','ConnectionString':'','ServiceName':'MOBILELENDING','FunctionName':'GETNAME','MeterNumber':'','Country':'KENYA','ExternalRequest':'YES','InfoField1':'REGISTRATION','InfoField2':'SAFARICOM','InfoField3':'VALIDATE','InfoField4':'USSD','InfoField5':'','InfoField6':'','InfoField7':'','InfoField8':'','InfoField9':'" . $this->msisdn . "','InfoField10':'','TrxSource':'USSD','MerchantReference':'" . time() . "','Amount':'0','CustomerFullName':'Barclays BANK','StoredProcedureName':''}}";
        $headers = array(
            'Content-Type:application/json',
            'Authorization: Basic ' . base64_encode("UserID:PassWord") // <---
        );

        $this->logReg($this->msisdn, "REGISTRATION VALIDATION REQUEST : " . $postData);

        $url = "http://172.17.20.25:51102/MobileLendingBBK/MobileLending.asmx/Barclays";

        $ch = curl_init();
        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, $postData);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //execute post
        $result = curl_exec($ch);
        //close connection
        curl_close($ch);

        $this->logReg($this->msisdn, "REGISTRATION VALIDATION REPONSE : " . $result);

        $res = explode(":", $result);

        if ($res[1] == "091") {
            $response = (object) array('id' => 'TimizaRegistrationValidation', 'action' => 'con', 'response' => "Welcome to Timiza!\nPlease enter your ID Number to register:\n\nI accept Timiza Product T&Cs visit www.barclays.co.ke- Product Terms and conditions section\n000. Exit", 'map' => array((object) array('menu' => 'TimizaRegistrationConfirm')), 'type' => 'form');
        } else {
            $response = (object) array('id' => 'TimizaRegistrationValidation', 'action' => 'end', 'response' => $res[3], 'map' => array((object) array('menu' => 'default')), 'type' => 'static');
        }

        return $response;
    }

    public function TimizaRegistrationConfirm() {
        $IDNumber = Cache::get($this->msisdn . 'TimizaRegistrationValidation');
        $postData = "{meterDetails:{'UniqueID':'" . $this->guid() . "','MobileNumber':'" . $this->msisdn . "','BankID':'03','CustomerID':'25400003','Request':'','Response':'','StanNumber':'0','ConnectionString':'','ServiceName':'MOBILELENDING','FunctionName':'GETNAME','MeterNumber':'','Country':'KENYA','ExternalRequest':'YES','InfoField1':'REGISTRATION','InfoField2':'SAFARICOM','InfoField3':'NEW','InfoField4':'USSD','InfoField5':'" . $IDNumber . "','InfoField6':'','InfoField7':'','InfoField8':'','InfoField9':'" . $this->msisdn . "','InfoField10':'','TrxSource':'USSD','MerchantReference':'" . time() . "','Amount':'0','CustomerFullName':'Barclays BANK','StoredProcedureName':''}}";
        $headers = array(
            'Content-Type:application/json',
            'Authorization: Basic ' . base64_encode("UserID:PassWord") // <---
        );

        $this->logReg($this->msisdn, "REGISTRATION REQUEST : " . $postData);

        $url = "http://172.17.20.25:51102/MobileLendingBBK/MobileLending.asmx/Barclays";

        $ch = curl_init();
        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, $postData);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //execute post
        $result = curl_exec($ch);
        //close connection
        curl_close($ch);

        $this->logReg($this->msisdn, "REGISTRATION RESPONSE : " . $result);

        $res = explode(":", $result);

        if ($res[1] == "") {
            $response = (object) array('id' => 'TimizaRegistrationConfirm', 'action' => 'end', 'response' => $res[3], 'map' => array((object) array('menu' => 'default')), 'type' => 'static');
        } else {
            $response = (object) array('id' => 'TimizaRegistrationConfirm', 'action' => 'end', 'response' => $res[3], 'map' => array((object) array('menu' => 'default')), 'type' => 'static');
        }
        return $response;
    }

    public function ServiceDown() {
        return $response = (object) array('action' => 'end', 'response' => 'Service is currently unavailable please try again later \n00. Back \n000. Logout', 'map' => array((object) array('menu' => 'ServiceDown')), 'type' => 'static');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        //
    }

}
