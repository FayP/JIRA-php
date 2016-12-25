<?php
/**
 * JIRA Rest Client
 *
 * @author     Original Author https://github.com/FayP / http://faypickering.com
 * @author     Francisco Mancardi <francisco.mancardi@gmail.com>
 */

namespace JiraApi;

class Jira
{

    protected $host;

    public function __construct(array $config = array())
    {
        // Check config before do nothing
        $this->request = new RestRequest();
        $this->request->username = (isset($config['username'])) ? trim($config['username']) : null;
        $this->request->password = (isset($config['password'])) ? trim($config['password']) : null;
        $this->host = (isset($config['host'])) ? trim($config['host']) : null; 
        
        $this->configCheck();
        $this->host = trim($this->host,"/") . '/'; 

        if( ($last = $this->host[strlen($this->host)-1]) != '/' )
        {
            $this->host .= '/';
        }

    }

    /**
     *
     */
    private function configCheck()
    {
        if(is_null($this->host) || $this->host == '')
        {
            throw new \Exception('Missing or Empty host (url to API) - unable to continue');      
        }    
        if(is_null($this->request->username) || $this->request->username == '' )
        {
            throw new \Exception('Missing or Empty username - unable to continue');      
        }    
        if(is_null($this->request->password) || $this->request->password == '')
        {
            throw new \Exception('Missing or Empty password - unable to continue');      
        }    
    }

    /**
     *
     */
    public function testLogin()
    {
        $user = $this->getUser($this->request->username);
        if (!empty($user) && $this->request->lastRequestStatus()) {
            return true;
        }

        return false;
    }

    /**
     * https://docs.atlassian.com/jira/REST/latest/#api/2/user-getUser
     */
    public function getUser($username)
    {
        $this->request->openConnect($this->host . 'user/?username=' . $username, 'GET');
        $this->request->execute();
        $user = json_decode($this->request->getResponseBody());

        return $user;
    }

    /**
     *
     */
    public function getStatuses()
    {
        $this->request->openConnect($this->host . 'status', 'GET');
        $this->request->execute();
        $statuses = json_decode($this->request->getResponseBody());
        $returnStatuses = array();
        foreach ($statuses as $status) {
            $returnStatuses[$status->id] = $status->name;
        }

        return $returnStatuses;
    }

    public function getTransitions($issueKey)
    {
        $this->request->openConnect($this->host . 'issue/' . $issueKey . '/transitions', 'GET');
        $this->request->execute();
        if ($result = json_decode($this->request->getResponseBody())) {
            $returnTransitions = array();
            foreach ($result->transitions as $transition) {
                $returnTransitions[$transition->id] = $transition->name;
            }
            return $returnTransitions;
        }

        return false;
    }

    public function getChangelog($issueKey, $historyAsText = true)
    {
        $this->request->openConnect($this->host . 'issue/' . $issueKey . '/?expand=changelog', 'GET');
        $this->request->execute();
        if ($result = json_decode($this->request->getResponseBody())) {
            if (!isset($result->changelog)) {
                return false;
            }
            $changeLog = array();
            $histories = $result->changelog->histories;
            if ($historyAsText) {
                foreach ($histories as $history) {
                    $changeLog[$history->author->name] = array(
                        'Created:' => $history->created,
                        var_export($history->items, true)
                    );
                }
            } else {
                foreach ($histories as $history) {
                    $changeLog[$history->author->name] = array(
                        'Created:' => $history->created,
                        $history->items, true
                    );
                }
            }
            return $changeLog;
        }

        return false;
    }

    public function getComments($issueKey)
    {
        $this->request->openConnect($this->host . 'issue/' . $issueKey . '/comment?expand', 'GET');
        $this->request->execute();
        $result = json_decode($this->request->getResponseBody());
        if (isset($result->comments)) {
            return $result->comments;
        }

        return false;
    }

    public function queryIssue($query)
    {
        function createPairs($obj) {
            $str = "";
            foreach ($obj as $key => $value) {
                if ($key != 'jql') {
                    $str .= "$key=$value&";
                } else {
                    $str .= trim($value, '"\'@') . '&';
                }
            }
            return rtrim($str, '&');
        }
        $qs = createPairs($query);
        $qs = urlencode($qs);
        $this->request->OpenConnect($this->host . 'search?jql=' . $qs);
        $this->request->execute();
        $result = json_decode($this->request->getResponseBody());
        if (isset($result->issues)) {
            return $result->issues;
        }

        return false;
    }

    /**
     *
     * @param array $issueFields using 'fields' member
     *
     * Here's an example:
     *
     * $issueFields = array('fields' =>
     *                       array('project' => array('key' => (string)'ZOFF'),
     *                             'summary' => 'My First JIRA Issue via REST',
     *                             'description' => '',
     *                             'issuetype' => array( 'id' => 1)
     *                            )
     *                     );
     *
     * For more details about fields:
     * https://developer.atlassian.com/display/JIRADEV/
     *       JIRA+REST+API+Example+-+Create+Issue#JIRARESTAPIExample-CreateIssue-Examplesofcreatinganissue
     *
     * https://developer.atlassian.com/display/JIRADEV/
     *       JIRA+REST+API+Example+-+Discovering+meta-data+for+creating+issues
     *
     *
     * @return  object reponse body (ATTENTION: can be null if something wrong has happened) 
     *          properties: id,key,self
     *          Example:
     *          {"id":"12505","key":"ZOFF-186","self":"https://testlink.atlassian.net/rest/api/latest/issue/12505"}
     *
     */
    public function createIssue($issueFields)
    {
        $this->request->openConnect($this->host . 'issue/', 'POST', $issueFields);
        $this->request->execute();

        return json_decode($this->request->getResponseBody());
    }

    /**
     *
     *
     */
    public function addAttachment($filename, $issueKey)
    {
        $this->request->openConnect($this->host . 'issue/' . $issueKey . '/attachments', 'POST', null, $filename);
        $this->request->execute();

        return $this->request->lastRequestStatus();
    }

    /**
     *
     * @param array $issueFields using 'fields' member
     *
     */
    public function updateIssue($issueFields, $issueKey)
    {
        $this->request->openConnect($this->host . 'issue/' . $issueKey, 'PUT', $issueFields);
        $this->request->execute();

        return $this->request->lastRequestStatus();
    }

    public function transitionIssue($issue, $transitionId)
    {
        $transitionId = (int) $transitionId;
        $data = array('transition' => array('id' => $transitionId));
        $this->request->openConnect($this->host . 'issue/' . $issue . '/transitions', 'POST', $data);
        $this->request->execute();

        return $this->request->lastRequestStatus();
    }

    public function addComment($comment, $issueKey)
    {
        $newComment = array(
            "body"	=> $comment,
        );

        $this->request->openConnect($this->host . 'issue/' . $issueKey . '/comment', 'POST', $newComment);
        $this->request->execute();

        return $this->request->lastRequestStatus();
    }

    public function getIssue($issueKey)
    {
        $this->request->openConnect($this->host . 'issue/' . $issueKey, 'GET');
        $this->request->execute();
        $item = json_decode($this->request->getResponseBody());

        return $item;
    }
    
    /**
     * return a map where main key is projectkey (if call has returned infor
     * for this key)
     * Each element is an map with issueTypeID as key, and each element inside
     * this map has to elements with two keys:
     * - issueTypeName
     * - fields => array with field names
     *
     * Here a partial example for project with key ZOFF
     * array(1) {
     *    ["ZOFF"]=>
     *         array(7) {
     *           [1]=>
     *           array(2) {
     *             ["issueTypeName"]=> "Bug"
     *             ["fields"]=> array(21) {
     *                           ["summary"] => "summary"
     *                           ["reporter"] => "reporter" 
     */
    public function getCreateIssueFields($projectKeys=null)
    {
        $opt = 'expand=projects.issuetypes.fields';
        $items = $this->getCreateIssueMetadata($projectKeys,$opt);
        $ret = null;
        if(!is_null($items) && count($items->projects) > 0)
        {
            $ro = &$items->projects;
            foreach($ro as $ele)
            {
                $ret[$ele->key] = array();
                $rx = &$ele->issuetypes;
                foreach($rx as $it)
                {
                    $ret[$ele->key][$it->id]['issueTypeName'] = $it->name;
                    foreach($it->fields as $field)
                    {
                      $ret[$ele->key][$it->id]['fields'][$field->key] = $field->key;
                    } 
                }    
            }    
            //return $items->projects;
        }
        return $ret; 
    }



    /**
     * https://docs.atlassian.com/jira/REST/cloud/#api/2/issue-getCreateIssueMeta
     *
     * https://developer.atlassian.com/jiradev/jira-apis/jira-rest-apis/
     *       jira-rest-api-tutorials/jira-rest-api-example-discovering-meta-data-for-creating-issues
     *
     *
     * curl -D- -u fred:fred -X GET -H "Content-Type: application/json" \
     *      http://kelpie9:8081/rest/api/2/issue/createmeta   
     *
     * curl -D- -u fred:fred -X GET -H "Content-Type: application/json" \
     *      http://kelpie9:8081/rest/api/2/issue/createmeta?projectKeys=QA     
     * 
     * curl -D- -u fred:fred -X GET -H "Content-Type: application/json" \
     *      http://kelpie9:8081/rest/api/2/issue/createmeta?projectKeys=QA,XSS     
     *
     * From Atlassian documentation
     * projectKeys  string  
     *              lists the projects with which to filter the results. 
     *              If absent, all projects are returned. 
     *              This parameter can be comma-separated list. 
     *              Specifiying a project that does not exist 
     *              (or that you cannot create issues in) is not an error, 
     *              but it will not be in the results.
     *
     * opt can contain issuetypeIds, issuetypeNames, expand=projects.issuetypes.fields.
     *     Fields will only be returned if expand=projects.issuetypes.fields.
     */
    public function getCreateIssueMetadata($projectKeys=null,$opt=null)
    {
        $cmd = $this->host . 'issue/createmeta';
        $ope = '?';
        if( !is_null($projectKeys) )
        {
           $cmd .= $ope . 'projectKeys=' . $projectKeys;
           $ope = '&';
        }

        if( !is_null($opt) )
        {
           $cmd .= $ope . $opt;
        }

        $this->request->openConnect($cmd, 'GET');
        $this->request->execute();
        $items = json_decode($this->request->getResponseBody());

        return $items;
    }

}