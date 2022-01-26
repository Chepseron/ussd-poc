<?php

namespace App\UssdTraits;

trait Security
{

    public function encrypt($plaintext) {
            $iv = "84jfkfndl3ybdfkf";
            $key = "f080786e3a348458a621e2fa4c267ad8";
            $data = openssl_encrypt($plaintext, 'AES256', $key, 0, $iv);
			//$data = base64_encode($data);
			//$data = str_replace("+","-",$data);
			return $data;
		}
		public function decrypt($encryptedtext){
            $iv = "84jfkfndl3ybdfkf";
            $key = "f080786e3a348458a621e2fa4c267ad8";
            $data = openssl_decrypt($encryptedtext, 'AES256', $key, 0, $iv);
            echo "Decrypted : ".$data;
			//$decrypted = base64_decode($data);
			return $data;
		}
}
