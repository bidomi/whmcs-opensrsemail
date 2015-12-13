<?php
class openSRS_mail {
	
	protected $username;
	
	protected $password;
	
	protected $domain;
	
	protected $cluster;
	
	protected $mode;

	public function createDomain($domain, /* Added by DD - 16/05/2015 : One parametr for new API to create Domain*/$create = true/* END */,$timezone = null, $language = null, $filtermx = null, $spamTag = null, $spamFolder = null, $spamLevel = null) {
		
        /* Old Code : Commented by DD - 16/05/2015 */ 
		/*$compile = "";
		$compile .= " domain=\"".$domain."\"";
		if(!empty($timezone)) $compile .= " timezone=\"".$timezone."\"";
		if(!empty($language)) $compile .= " language=\"".$language."\"";
		if(!empty($filtermx)) $compile .= " filtermx=\"".$filtermx."\"";
		if(!empty($spamTag)) $compile .= " spam_tag=\"".$spamTag."\"";
		if(!empty($spamFolder)) $compile .= " spam_folder=\"".$spamFolder."\"";
        if(!empty($spamLevel)) $compile .= " spam_level=\"".$spamLevel."\"";
        return $this->_processRequest ("create_domain", $compile); */
        /* END */
        
        /* Added by DD - 16/05/2015 : Array object to create domain (For new api) */
        $compile = array("attributes"=>new ArrayObject(),"domain"=>$domain,"create_only"=>$create,"credentials"=>array("user"=>$this->username,"password"=>$this->password));
        
        /* Check if domain already exists */
        $getParams = array("credentials"=>array("user"=>$this->username,"password"=>$this->password),"domain"=>$domain);   
        $result =  $this->_processRequest ("get_domain", $getParams);  
        if(!$result["is_success"])
        {
		    return $this->_processRequest ("change_domain", $compile);
        }
        else
        {
            $result=Array(
            "is_success" => "0",
            "response_code" => "7",
            "response_text" => "This domain already exists."
            );
            return $result;
        }
        /* END */
	}

	public function disableDomain($domain) {
        /* Old Code : Commented by DD - 16/05/2015 */ 
		/*$compile = " domain=\"".$domain."\"";
		$compile .= " disabled=\"T\"";  */
        /* END */ 
        
         /* Added by DD - 16/05/2015 : Array object to suspend domain (For new api) */
        $compile = array("attributes"=>array("disabled"=>true),"domain"=>$domain,"credentials"=>array("user"=>$this->username,"password"=>$this->password));
		
		return $this->_processRequest ("change_domain", $compile);
        /* END */
	}
	
	public function enableDomain($domain) {
        /* Old Code : Commented by DD - 16/05/2015 */ 
		/*$compile = " domain=\"".$domain."\"";
		$compile .= " disabled=\"F\""; */
        /* END */
        
        /* Added by DD - 16/05/2015 : Array object to unsuspend domain (For new api) */
        $compile = array("attributes"=>array("disabled"=>false),"domain"=>$domain,"credentials"=>array("user"=>$this->username,"password"=>$this->password));
		
		return $this->_processRequest ("change_domain", $compile);
        /* END */
	}

	public function deleteDomain($domain) {
        /* Old Code : Commented by DD - 16/05/2015 */ 
		/*$compile = " domain=\"".$domain."\"";
		$compile .= " cascade=\"T\""; */
        /* END */
        
        /* Added by DD - 16/05/2015 : Array object to delete domain (For new api) */
		$compile = array("credentials"=>array("user"=>$this->username,"password"=>$this->password),"domain"=>$domain);
		return $this->_processRequest ("delete_domain", $compile);
        /* END */
	}

	public function getDomainMailboxes($domain) {
		/*Old Code : Commented by DD - 27/05/2015 */
        /* $compile = " domain=\"".$domain."\""; */
        /* END */
        
        /* Added by DD - 27/05/2015 : Array object to Get Mailbox (For new api) */


		/*
		$domain_info = $this->getDomain($main_domain);			
		$domain_list[] = $main_domain;

		foreach ($domain_info['attributes']['aliases'] as $domain_alias) {
			$domain_list[] = $domain_alias;
		}
		*/
		

	
		$compile = array("credentials"=>array("user"=>$this->username,"password"=>$this->password),"criteria"=>array("domain"=>$domain));
        
        $response = $this->_processRequest ("search_users", $compile);
        $result["is_success"]    =  $response["is_success"];
        $result["response_code"] =  $response["response_code"];
        $result["response_text"] =  $response["response_text"];
        
        $users =  json_decode(json_encode($response['response']['users']), true);
        
        
        for($j=0;$j<count($users);$j++)
        {    

    		// totals
            if($users[$j]['type'] == 'mailbox')
            {

               $result["attributes"]["mailbox"] += 1;
            }
            elseif($users[$j]['type'] == 'forward')
            {

               $result["attributes"]["forward"] += 1;
            }
            elseif($users[$j]['type'] == 'filter')
            {
               $result["attributes"]["filter"] += 1;
            }




            $user = array("mailbox"=>$users[$j]['user'],"type"=>$users[$j]['type'],"workgroup"=>$users[$j]['workgroup']);

			if ($users[$j]['type'] != 'mailbox' && $users[$j]['type'] != 'forward')
			{				
				continue;
			}

            $result["attributes"]["list"][$users[$j]['user']] = $user;



        }

		// alias
        for($j=0;$j<count($users);$j++)
        {    
            if ($users[$j]['type'] == 'alias' && !empty($users[$j]['alias_target']))
            {
            	$result["attributes"]["list"][$users[$j]['alias_target']]['aliases'][] = $users[$j]['user'];
            }
            else if ($users[$j]['type'] == 'forward')
            {
            	$FWInfo = $this->getMailboxForwardOnly('', $users[$j]['user']);
				$result["attributes"]["list"][$users[$j]['user']]['forward_email'] = explode("\n", $FWInfo['attributes']['forward_email']);
            }
        }  			
	
		   

		ksort($result["attributes"]["list"]);

        return $result;
        /* END */
		//return $this->_processRequest ("get_domain_mailboxes", $compile);
	}

	public function validateAliasForward($domain, $user, $addresses)
	{

		if (count($addresses) < 1 || !$user)
		{
			return false;
		}



		$domain_info = $this->getDomain($domain);			
	

		// banned emails
		foreach ($domain_info['attributes']['aliases'] as $domain_alias) 
		{
			$domain_alias = strtolower($domain_alias);
			$banned_email[$user."@".$domain_alias] = true;
			$valid_domains[$domain_alias] = true;
		}
		$banned_email[$user."@".$domain] = true;
		$valid_domains[$domain] = true;


		foreach($addresses as $ii=>$address)
		{
			$address = strtolower($address);
			list($tmp_user, $tmp_domain) = explode("@", $address);


			if ($valid_domains[$tmp_domain])
			{
				$address = $tmp_user."@".$domain;
			}



			if ($banned_email[$address])
			{
				continue;
			}

			$tmp_addresses[$address] = $address;
			

			
		}	

		foreach($tmp_addresses as $ii)
		{
			$valid_addresses[] = $ii;
		}



		return $valid_addresses;
		

	}

	public function getNumDomainMailboxes($domain) {
        /*Old Code : Commented by DD - 27/05/2015 */
		/* $compile = " domain=\"".$domain."\""; */
		/* END */
        
         /* Added by DD - 27/05/2015 : Array object to Get Mailbox Count (For new api) */
        $compile = array("credentials"=>array("user"=>$this->username,"password"=>$this->password),"criteria"=>array("domain"=>$domain));
        
		$response = $this->_processRequest ("search_users", $compile);
        $result["is_success"]    =  $response["is_success"];
        $result["response_code"] =  $response["response_code"];
        $result["response_text"] =  $response["response_text"];

        
        $users =  json_decode(json_encode($response['response']['users']), true);
        
        $result["attributes"]["mailbox"] = 0;
        $result["attributes"]["forward"] = 0;
        $result["attributes"]["filter"]  = 0;
        for($j=0;$j<count($users);$j++)
        {    
            if($users[$j]['type'] == 'mailbox')
               $result["attributes"]["mailbox"] += 1;
            elseif($users[$j]['type'] == 'forward')
               $result["attributes"]["forward"] += 1;
            elseif($users[$j]['type'] == 'filter')
               $result["attributes"]["filter"] += 1;
        }
       
        return $result;
        
	}


	public function getDomain($domain) {
        /*Old Code : Commented by DD - 27/05/2015 */
		/* $compile = " domain=\"".$domain."\""; */
		/* END */
        
         /* Added by DD - 27/05/2015 : Array object to Get Mailbox Count (For new api) */
        $compile = array("credentials"=>array("user"=>$this->username,"password"=>$this->password),"domain"=>$domain);
        
		$response = $this->_processRequest ("get_domain", $compile);
			
        $result["is_success"]    =  $response["is_success"];
        $result["response_code"] =  $response["response_code"];
        $result["response_text"] =  $response["response_text"];
        

        $attributes =  json_decode(json_encode($response['response']['attributes']), true);
        
        $result['attributes']['aliases'] = $attributes['aliases'];
        
        return $result;
        
	}


	public function getDomainWorkgroups($domain) {
         /* Old Code : Commented by DD - 26/05/2015 */ 
		/*$compile = " domain=\"".$domain."\""; */
		 /* END */
         
         /* Added by DD - 26/05/2015 - Get Workgroups by Domain name */
        $compile = array("credentials"=>array("user"=>$this->username,"password"=>$this->password),"criteria"=>array("domain"=>$domain)); 
      
        
		$response = $this->_processRequest ("search_workgroups", $compile);
        $result["is_success"]    =  $response["is_success"];
        $result["response_code"] =  $response["response_code"];
        $result["response_text"] =  $response["response_text"];
        $result["attributes"]["list"] =  array();
        
        $workgroups =  json_decode(json_encode($response['response']['workgroups']), true);

        for($j=0;$j<$response['response']['total_count'];$j++)
        {    
            $counts = array("workgroup"=>$workgroups[$j]['workgroup'],"mailbox_count"=>$workgroups[$j]['counts']['mailbox'],"forward_count"=>$workgroups[$j]['counts']['forward'],"alias_count"=>$workgroups[$j]['counts']['filter']);
            $result["attributes"]["list"][] = $counts;
        }
        return $result;
        /* END */
	}
	
	public function getMailbox($domain, $mailbox) {
         /* Old Code : Commented by DD - 26/05/2015 */ 
		/*$compile = " domain=\"".$domain."\"";
		$compile .= " mailbox=\"".$mailbox."\"";  
        return $this->_processRequest("get_mailbox", $compile); */
         /* END */ 
		
		 /* Added by DD - 26/05/2015 - Get Workgroups by Domain name */
        $compile = array("credentials"=>array("user"=>$this->username,"password"=>$this->password),"user"=>$mailbox); 
        $response = $this->_processRequest ("get_user", $compile);
        
        $result["is_success"]    =  $response["is_success"];
        $result["response_code"] =  $response["response_code"];
        $result["response_text"] =  $response["response_text"];
        
        $user = json_decode(json_encode($response["response"]["attributes"]), true);


        $result["attributes"]['mailbox'] 	= $user['account'];
        $result["attributes"]['first_name'] = strtok($user["name"]," ");
        $result["attributes"]['title'] 		= $user['title'];
        $result["attributes"]['last_name'] 	= substr($user["name"], strpos($user["name"], " ") + 1);
        $result["attributes"]['phone'] 		= $user['phone'];
        $result["attributes"]['aliases'] 	= implode("\n",  $user['aliases']);
        
        return $result;
        /* END */
	}
	
	public function createMailbox($domain, $mailbox, $workgroup, $password, $firstName, $lastName, $title, $phone, $fax, $type) {
         /* Old Code : Commented by DD - 27/05/2015 */
		/*$compile = " domain=\"".$domain."\"";
		$compile .= " mailbox=\"".$mailbox."\"";
		$compile .= " workgroup=\"".$workgroup."\"";
		$compile .= " password=\"".$password."\"";
		$compile .= " first_name=\"".$firstName."\"";
		$compile .= " last_name=\"".$lastName."\"";
		$compile .= " title=\"".$title."\"";
		$compile .= " phone=\"".$phone."\"";
		$compile .= " fax=\"".$fax."\"";*/
		/* END */


        $attributes['fax'] = $fax;
        $attributes['name'] = $firstName." ".$lastName;        
        $attributes['password'] = $password;
        $attributes['phone'] = $phone;
        $attributes['type'] = $type;
        $attributes['workgroup'] = $workgroup;
        $attributes['title'] = $title;
        $attributes['aliases'] = $aliases;
        

       $compile = array("credentials"=>array("user"=>$this->username,"password"=>$this->password),"create_only"=>true,"user"=>$mailbox,"attributes"=> $attributes);  
       return $this->_processRequest("change_user", $compile); 
	}
	
	public function changeMailbox($domain, $mailbox, $workgroup, $password, $firstName, $lastName, $title, $phone, $fax, $aliases, $type) {
        /* Old Code : Commented by DD - 27/05/2015 */
		/*$compile = " domain=\"".$domain."\"";
		$compile .= " mailbox=\"".$mailbox."\"";
		$compile .= " workgroup=\"".$workgroup."\"";
		if(!empty($password)) $compile .= " password=\"".$password."\"";
		$compile .= " first_name=\"".$firstName."\"";
		$compile .= " last_name=\"".$lastName."\"";
		$compile .= " title=\"".$title."\"";
		$compile .= " phone=\"".$phone."\"";
		$compile .= " fax=\"".$fax."\"";
		
		return $this->_processRequest("change_mailbox", $compile);  */
        /* END */

        $attributes['fax'] = $fax;
        $attributes['name'] = $firstName." ".$lastName;        
        $attributes['phone'] = $phone;
        $attributes['type'] = $type;
        $attributes['workgroup'] = $workgroup;
        $attributes['title'] = $title;
        $attributes['aliases'] = $aliases?:[];

       if(!empty($password))
       {
         $attributes['password'] = $password;
       }


	   $compile = array("credentials"=>array("user"=>$this->username,"password"=>$this->password),"user"=>$mailbox,"attributes"=>$attributes);    

       return $this->_processRequest("change_user", $compile); 
	}
	
	public function getMailboxForwardOnly($domain, $mailbox) {
		

		/* Old Code : Commented by fer - 06/12/2015 */
		/*$compile = " domain=\"".$domain."\"";
		$compile .= " mailbox=\"".$mailbox."\"";*/
		

		$compile = array("credentials"=>array("user"=>$this->username,"password"=>$this->password),"user"=>$mailbox); 
        $response = $this->_processRequest ("get_user", $compile);
        
        $result["is_success"]    =  $response["is_success"];
        $result["response_code"] =  $response["response_code"];
        $result["response_text"] =  $response["response_text"];
        
        $user = json_decode(json_encode($response["response"]["attributes"]), true);        

        $result["attributes"]['mailbox'] 		= $user['account'];
        $result["attributes"]['forward_email'] 	= implode("\n",  $user['forward_recipients']);
        $result["attributes"]['aliases'] 	= implode("\n",  $user['aliases']);
        $result["attributes"]['workgroup'] 		= $user['workgroup'];
        
        
        return $result;

	}
	
	public function createMailboxForwardOnly($domain, $mailbox, $workgroup, $forwardEmails, $aliases, $type) {
        /* Old Code : Commented by DD (28/05/2015) */
		/*$compile = " domain=\"".$domain."\"";
		$compile .= " mailbox=\"".$mailbox."\"";
		$compile .= " workgroup=\"".$workgroup."\"";
		$compile .= " forward_email=\"".$forwardEmails."\"";
		
		return $this->_processRequest("create_mailbox_forward_only", $compile); */
        /* END */
       
		$attributes['workgroup'] 			= $workgroup;
		$attributes['delivery_forward'] 	= true;
		$attributes['type'] 				= $type;
		$attributes['forward_recipients'] 	= $forwardEmails;
		
		if ($aliases)
		{
			$attributes['aliases'] 				= $aliases;	
		}
		


       $compile = array("credentials"=>array("user"=>$this->username,"password"=>$this->password),"create_only"=>true,"user"=>$mailbox,"attributes"=>$attributes);  
       //$compile = array("credentials"=>array("user"=>$this->username,"password"=>$this->password),"create_only"=>true,"user"=>$mailbox,"attributes"=>array("workgroup"=>$workgroup,"delivery_forward"=>true,"type"=>$type,"forward_recipients"=>$forwardEmails));  
       return $this->_processRequest("change_user", $compile); 
	}
	
	public function changeMailboxForwardOnly($domain, $mailbox, $forwardEmails = [], $aliases = [], $type = "") {

         /* Old Code : Commented by DD (28/05/2015) */
		/*$compile = " domain=\"".$domain."\"";
		$compile .= " mailbox=\"".$mailbox."\"";
		$compile .= " forward_email=\"".$forwardEmails."\"";
		
		return $this->_processRequest("change_mailbox_forward_only", $compile); */
        /* END */
        

        $attributes['delivery_forward'] = true;
        $attributes['type'] = $type;
        $attributes['forward_recipients'] = $forwardEmails;        
        $attributes['aliases'] = $aliases?:[];
        
        print_r($attributes);

        $compile = array("credentials"=>array("user"=>$this->username,"password"=>$this->password),"user"=>$mailbox,"attributes"=>$attributes);  
        return $this->_processRequest("change_user", $compile); 
        

	}
	
	public function createAliasMailbox($domain, $alias, $mailbox) {
        /* Old Code : Commented by DD (28/05/2015) */
		/*$compile = " domain=\"".$domain."\"";
		$compile .= " mailbox=\"".$mailbox."\"";
		$compile .= " alias_mailbox=\"".$alias."\"";
		
		return $this->_processRequest("create_alias_mailbox", $compile);*/
        /* END */
        
        $aliasEmails = explode(',',$alias);
        $compile = array("credentials"=>array("user"=>$this->username,"password"=>$this->password),"user"=>$mailbox,"attributes"=>array("aliases"=>$aliasEmails)); 
        return $this->_processRequest("change_user", $compile);
	}
	
	public function deleteMailboxAny($domain, $mailbox) {
        /* Old Code : Comments by DD - 27/05/2015 */
		/*$compile = " domain=\"".$domain."\"";
		$compile .= " mailbox=\"".$mailbox."\"";
		
		return $this->_processRequest("delete_mailbox_any", $compile);*/
        /* END */
        
        $compile = array("credentials"=>array("user"=>$this->username,"password"=>$this->password),"user"=>$mailbox);
        return $this->_processRequest("delete_user", $compile);
	}
	
	public function createWorkgroup($domain, $workgroup) {
         /* Old Code : Commented by DD - 26/05/2015 */ 
		/*$compile = " domain=\"".$domain."\"";
		$compile .= " workgroup=\"".$workgroup."\"";  */
        /* END */
        /* Added by DD - 26/05/2015 : Change Array of Cerate workgroup for CURL Call */
        $compile = array("credentials"=>array("user"=>$this->username,"password"=>$this->password),"domain"=>$domain,"workgroup"=>$workgroup);
		/* END */
		return $this->_processRequest("create_workgroup", $compile);
	}
	
	public function deleteWorkgroup($domain, $workgroup) {
         /* Old Code : Commented by DD - 26/05/2015 */ 
		/*$compile = " domain=\"".$domain."\"";
		$compile .= " workgroup=\"".$workgroup."\"";      */
		/* END */
         /* Added by DD - 26/05/2015 : Change Array of Cerate workgroup for CURL Call */
         $compile = array("credentials"=>array("user"=>$this->username,"password"=>$this->password),"domain"=>$domain,"workgroup"=>$workgroup);
        /* END */
		return $this->_processRequest("delete_workgroup", $compile);
	}

	// Post validation functions
	private function _processRequest($method, $command = ""){
       /*
		$sequence = array (
			0 => "ver ver=\"3.5\"",
			1 => "login user=\"". $this->username ."\" domain=\"". $this->domain ."\" password=\"". $this->password ."\"",
			2 => $method. $command,
			3 => "quit"
		);*/	
        
        /* Changed by DD : 16/05/2015 - Added one array element to pass array for spcific methods*/
        $sequence = array (
            0 => "ver ver=\"3.5\"",
            1 => "login user=\"". $this->username ."\" domain=\"". $this->domain ."\" password=\"". $this->password ."\"",
            2 => $method,
            3 => $command,
            4 => "quit"
        );
        /* END */	

		$tucRes = $this->makeCall($sequence);
		return $this->parseResults35($tucRes);
	}
	
	// Class constructor
	public function __construct ($username, $password, $domain, $cluster, $mode) {
		$this->username = $username;
		$this->password = $password;
		$this->domain = $domain;
		$this->cluster = strtolower($cluster);
		$this->mode = strtolower($mode);
	}

	// Class destructor
	public function __destruct () {
	}

	// Class functions
	protected function makeCall ($sequence){
        /* Added by DD : 16/05/2015 - New API Calls */
		$result = '';
		
		if ($this->mode == 'live')
		{
			$url = 'https://admin.'.$this->cluster.'.hostedemail.com/api/'.$sequence[2];
		}
		else
		{
			$url = 'https://admin.'.$this->mode.'.hostedemail.com/api/'.$sequence[2];
		}
        

        $data_string = json_encode($sequence[3]) ;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($data_string))); 
 
        $response = curl_exec($ch);
        $getInfo = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        curl_close($ch);

/*
		if ($sequence[2] == 'get_user')
		{
			print_r($response);
			echo $getInfo;
			exit;
		}
*/		

        if($getInfo >= '200' && $getInfo <= '206' )
        {
            $result = $response;
        }
        else
        {
            throw new Exception("Error connecting to OpenSRS");
        }
        
        /* END */
        /*
		// Open the socket
		error_log("ssl://admin.".$this->cluster.".hostedemail.com");
		// $fp = fsockopen ($this->osrs_host, $this->osrs_port, $errno, $errstr, $this->osrs_portwait);
		$fp = pfsockopen ("ssl://admin.".$this->cluster.".hostedemail.com", "4449", $errno, $errstr, "10");

		if (!$fp) {
			throw new Exception("Error connecting to OpenSRS");			// Something went wrong
		} else {
			// Send commands to APP server
			for ($i=0; $i<count($sequence); $i++){
				$servCatch = "";
			
				// Write the port
				$writeStr = $sequence[$i] ."\r\n";
				$fwrite = fwrite($fp, $writeStr);
				if (!$fwrite) 
					throw new Exception("Error connecting to OpenSRS");			// Something went wrong

				$dotStr = ".\r\n";
				$fwrite = fwrite($fp, $dotStr);
				if (!$fwrite)
					throw new Exception("Error connecting to OpenSRS");			// Something went wrong
								
							// read the port rightaway
				// Last line of command has be done with different type of reading
				if ($i == (count($sequence)-1) ){
					// Loop until End of transmission
					while (!feof($fp)) {
						$servCatch .= fgets($fp, 128);
					}
				} else {
					// Plain buffer read with big data packet
					$servCatch .= fread($fp, 8192);
				}
				
				// Possible parsing and additional validation will be here
				// If error accours in the communication than the script should quit rightaway
				// $servCatch
				
				$result .= $servCatch;
			}
		}

		//Close the socket
		fclose($fp);      */
		return $result;
	}

	protected function parseResults34 ($resString) {
		// Raw tucows result
		$resArray = explode (".\r\n",$resString);
		$resRinse = array ();
		for ($i=0; $i<count($resArray); $i++){							// Clean up \n, \r and empty fields
			$resArray[$i] = str_replace("\r", "", $resArray[$i]);
			$resArray[$i] = str_replace("\n", " ", $resArray[$i]);		// replace new line with space
			$resArray[$i] = str_replace("  ", " ", $resArray[$i]);		// no double space - for further parsing
			$resArray[$i] = substr($resArray[$i], 0, -1);				// take out the last space
			if ($resArray[$i] != "") array_push($resRinse, $resArray[$i]);
		}
    $result=Array(
			"is_success" => "1",
			"response_code" => "200",
			"response_text" => "Command completed successfully"
		);
		$i=1;
		// Takes the rinsed result lines and forms it into an Associative array
		foreach($resRinse as $resultLine){
			$okPattern='/^OK 0/';
			$arrayPattern = '/ ([\w\-\.\@]+)\=\"([\w\-\.\@\*\, ]*)\"/';
			$errorPattern = '/^ER ([0-9]+) (.+)$/';

			// Checks to see if this line is an information line
			$okLine = preg_match($okPattern, $resultLine, $matches);

	                if ($okLine == 0){
				// If it's not an ok line, it's an error
				$err_num_match=0;
	                        $err_num_match = preg_match($errorPattern,$resultLine,$err_match);

				// Makes sure the error pattern matched and that there isn't an error that has already happened
				if ($err_num_match==1 && $result['is_success']=="1"){
					$result['response_text']=$err_match[2];
					$result['response_code']=$err_match[1];
					$result['is_success']='0';
				}

			} else {
				// If it's an OK line check to see if it's an Array of values
				$arrayMatch=preg_match_all($arrayPattern, $resultLine, $arrayMatches);
				if ($arrayMatch !=0){
					for($j=0;$j<$arrayMatch;$j++){
						if($arrayMatches[1][$j]=="LIST")
							$result['attributes'][strtolower($arrayMatches[1][$j])]=explode("," , $arrayMatches[2][$j]);
						else
							$result['attributes'][strtolower($arrayMatches[1][$j])]=$arrayMatches[2][$j];
					}
				} else {

					// If it's not an array line or an error it could be a table
					$tableLines=explode(' , ', $resultLine);
					if (count($tableLines)>1){
						$tableLines[0] = str_replace("OK 0 ", "", $tableLines[0]);
						$tableHeaders=explode(' ',$tableLines[0]);
						$result['attributes']['list']=Array();
						for($j=1;$j<count($tableLines);$j++){
							$values=explode('" "', $tableLines[$j]);
							$k = 0;
							foreach($tableHeaders as $tableHeader){
								$result['attributes']['list'][$j-1][strtolower($tableHeader)]=str_replace('"', '', $values[$k]);
								$k++;
							}
						}

					}
				}
			}
			$i++;
		}

		return $result;
	}

	protected function parseResults35 ($resString) {
		// Raw tucows result
		$resArray = (array) json_decode($resString);
        $result=Array(
            "is_success" => "1",
            "response_code" => "200",
            "response_text" => "Command completed successfully",
            "response" => $resArray
        );
        
        if ($resArray['success']=="0" && isset($resArray['success']))
        {
            $result['response_text']=$resArray['error'];    
            $result['response_code']=$resArray['error_number'];
            $result['is_success']='0';
        }
                
		/* $resRinse = array ();
		for ($i=0; $i<count($resArray); $i++){							// Clean up \n, \r and empty fields
			$resArray[$i] = str_replace("\r", "", $resArray[$i]);
			$resArray[$i] = str_replace("\n", " ", $resArray[$i]);		// replace new line with space
			$resArray[$i] = str_replace("  ", " ", $resArray[$i]);		// no double space - for further parsing
			$resArray[$i] = substr($resArray[$i], 0, -1);				// take out the last space
			if ($resArray[$i] != "") array_push($resRinse, $resArray[$i]);
		}
    $result=Array(
			"is_success" => "1",
			"response_code" => "200",
			"response_text" => "Command completed successfully"
		);
		$i=1;
		// Takes the rinsed result lines and forms it into an Associative array
		foreach($resRinse as $resultLine){
			$okPattern='/^OK 0/';
			$arrayPattern = '/ ([\w\-\.\@]+)\=\"([\w\-\.\@\*\, ]*)\"/';
			$errorPattern = '/^ER ([0-9]+) (.+)$/';

			// Checks to see if this line is an information line
			$okLine = preg_match($okPattern, $resultLine, $matches);

	                if ($okLine == 0){
				// If it's not an ok line, it's an error
				$err_num_match=0;
	            $err_num_match = preg_match($errorPattern,$resultLine,$err_match);

				// Makes sure the error pattern matched and that there isn't an error that has already happened
				if ($err_num_match==1 && $result['is_success']=="1"){
					$result['response_text']=$err_match[2];
					$result['response_code']=$err_match[1];
					$result['is_success']='0';
				}

			} else {
				// If it's an OK line check to see if it's an Array of values
				$arrayMatch=preg_match_all($arrayPattern, $resultLine, $arrayMatches);
				if ($arrayMatch !=0){
					for($j=0;$j<$arrayMatch;$j++){
						if($arrayMatches[1][$j]=="LIST")
							$result['attributes'][strtolower($arrayMatches[1][$j])]=explode("," , $arrayMatches[2][$j]);
						else
							$result['attributes'][strtolower($arrayMatches[1][$j])]=$arrayMatches[2][$j];
					}
				} else {
					if(strpos($resultLine, "OK 0 Success ") === 0) {
						// If it's not an array line or an error it could be a table
						$tableLines=explode(' ', str_replace("OK 0 Success ", "", $resultLine));
						if (count($tableLines)>1){
							$tableHeaders=explode(',',$tableLines[0]);
							$result['attributes']['list']=Array();
							for($j=1;$j<count($tableLines);$j++){
								$values=explode('","', $tableLines[$j]);
								$k = 0;
								foreach($tableHeaders as $tableHeader){
									$result['attributes']['list'][$j-1][strtolower($tableHeader)]=str_replace('"', '', $values[$k]);
									$k++;
								}
							}
	
						}
					}
				}
			}
			$i++;
		}*/

		return $result;
	}
}
