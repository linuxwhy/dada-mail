<?php

Class DadaMailWebService { 

	public $public_key; 
	public $private_key; 
	public $server; 
	
	public function __construct($server, $public_key, $private_key) { 
        $this->server      = $server; 
		$this->public_key  = $public_key; 
		$this->private_key = $private_key; 
	}
	
	public function the_query_string($post_data){
		$raw_post_data  = http_build_query($post_data, null, "&", PHP_QUERY_RFC3986);
	   	return $raw_post_data;
	}
	
	public function digest($message) { 
		$digest = hash_hmac ('sha256', $message, $this->private_key, true);
		$digest = base64_encode($digest);
		return $digest; 
	}
	
	public function nonce() {
	    $chars = array(); 
        foreach (range(0, 9) as $n) {
        	array_push($chars, $n);
        }
        foreach (range('a', 'z') as $l) {
        	array_push($chars, $l);
        }
        foreach (range('A', 'Z') as $L) {
        	array_push($chars, $L);
        }
        $num   = 8; 
        $nonce = ''; 
        foreach(range(1,$num) as $foo){
            $nonce .= $chars[rand(0, (count($chars)-1))];
        }

        return $nonce;
	}
	
	public function request($list, $flavor, $params){ 
	    
	    $nonce = time() . ':' . $this->nonce() ;

        if($flavor == 'mass_email'){ 
            $query_params = array(
                'format'    => $params['format'],
                'message'   => $params['message'],
                'nonce'     => $nonce,
                'subject'   => $params['subject'],
            ); 
        }
        else {
    		$encoded_addresses = json_encode($params['addresses']);
    		$query_params = array(
    			'addresses' => $encoded_addresses,
    			'nonce'     => $nonce, 
    		);
    	}
        
		$rpd                 =  $this->the_query_string($query_params);
		$digest              =  $this->digest($rpd); 
		$request_method      = 'POST';
		$request_w_path_info = $this->server . '/api/' . urlencode($list) . '/' . urlencode($flavor) . '/'; 
		
		// make the request using curl
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_HTTPHEADER, 
		array(
            'Authorization: hmac ' .  ' ' . $this->public_key . ':' . $digest,
            )
        );
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); 
		curl_setopt($ch, CURLOPT_URL, $request_w_path_info);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($query_params, null, "&", PHP_QUERY_RFC3986));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		$response = curl_exec($ch);
		
		curl_close($ch);

		return $response; 
	}
}

?>