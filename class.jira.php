<?php
require_once('class.RestRequest.php');
class Jira {
	protected $project;
	protected $host;
	function __construct($config){
		$this->request = new RestRequest;
		$this->request->username = $config->username;
		$this->request->password = $config->password;
		$this->host = $config->host;
	}
	public function createIssue($json){
		$this->request->openConnect('https://'.$this->host.'/rest/api/latest/issue/', 'POST', $json);
		$this->request->execute();  
		echo '<pre>' . print_r($this->request, true) . '</pre>';

	}
	public function updateIssue($json, $issueKey){
		$this->request->openConnect('https://'.$this->host.'/rest/api/latest/issue/'.$issueKey, 'PUT', $json);
		$this->request->execute();  
		echo '<pre>' . print_r($this->request, true) . '</pre>';
	}
	public function queryIssue($query){
		
		function createPairs($obj){
			$str = "";
			foreach ($obj as $key => $value) {
    			$str .= "$key=$value&";
			}
			return rtrim($str, '&');
		}
		$qs = createPairs($query);
		$this->request->OpenConnect('https://'.$this->host.'/rest/api/latest/search?jql='.$qs);
		$this->request->execute();  
		echo '<pre>' . print_r($this->request, true) . '</pre>';
	}
	
}
?>