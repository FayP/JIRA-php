<?php
class RestRequest {
    public $username;  
    public $password; 
	protected $url;  
    protected $verb;  
    protected $requestBody;  
    protected $requestLength;   
    protected $acceptType;
    protected $responseBody; 
    protected $responseInfo;  
  
    public function openConnect ($url = null, $verb = 'GET', $requestBody = null){  
        $this->url               = $url;  
        $this->verb              = $verb;  
        $this->requestBody       = $requestBody;  
        $this->requestLength     = 0;
        $this->acceptType        = 'application/json';
        $this->responseBody      = null;  
        $this->responseInfo      = null;  
  
        if ($this->requestBody !== null)  
        {  
            $this->buildPostBody();  
        }  
    }  
  
    public function flush (){  
        $this->requestBody       = null;  
        $this->requestLength     = 0;  
        $this->verb              = 'GET';  
        $this->responseBody      = null;  
        $this->responseInfo      = null;  
    }  
  
    public function execute (){  
	    $ch = curl_init();  
	    $this->setAuth($ch);  
	  
	    try{  
	        switch (strtoupper($this->verb)){  
	            case 'GET':  
	                $this->executeGet($ch);  
	                break;  
	            case 'POST':  
	                $this->executePost($ch);  
	                break;  
	            case 'PUT':  
	                $this->executePut($ch);  
	                break;  
	            case 'DELETE':  
	                $this->executeDelete($ch);  
	                break;  
	            default:  
	                throw new InvalidArgumentException('Current verb (' . $this->verb . ') is an invalid REST verb.');  
	        }  
	    }catch (InvalidArgumentException $e){  
	        curl_close($ch);  
	        throw $e;  
	    }catch (Exception $e){  
	        curl_close($ch);  
	        throw $e;  
	    }
    }
  
    public function buildPostBody ($data = null){  
  		$data = ($data !== null) ? $data : $this->requestBody; 
  
    	$data = json_encode($data);
    	$this->requestBody = $data;  
    }  
  
    protected function executeGet ($ch){         
  		$this->doExecute($ch);
    }  
  
    protected function executePost ($ch){

    	curl_setopt($ch, CURLOPT_POST, true);  
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $this->requestBody);  
  
    	$this->doExecute($ch);  
    }  
  
    protected function executePut ($ch){ 
    	curl_setopt($ch, CURLOPT_POST, 1);  
    	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $this->requestBody);  
  
    	$this->doExecute($ch);  
    }  
  
    protected function executeDelete ($ch){  
  		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");  
  
    	$this->doExecute($ch);  
    }  
  
    protected function doExecute (&$ch){  
    	$this->setCurlOpts($ch);
    	$this->responseBody = curl_exec($ch);  
    	$this->responseInfo  = curl_getinfo($ch); 
    	curl_close($ch);
    }
  
    protected function setCurlOpts (&$ch){  
  		curl_setopt($ch, CURLOPT_TIMEOUT, 60);  
    	curl_setopt($ch, CURLOPT_URL, $this->url);  
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	curl_setopt($ch, CURLOPT_HEADER, true);
    	curl_setopt($ch, CURLOPT_HTTPHEADER, array ('Accept: ' . $this->acceptType, 'Content-Type: application/json'));  
    } 
  
    protected function setAuth (&$ch){
    	if ($this->username !== null && $this->password !== null){   
        	curl_setopt($ch, CURLOPT_USERPWD, $this->username . ':' . $this->password);  
    	}    
    }  
}
?>