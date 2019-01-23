<?php

namespace JiraApi;

class Jira
{

    /**
     * @var string
     */
    protected $host;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var RestRequest
     */
    protected $request;

    public function __construct()
    {
        $this->request = new RestRequest();

    }

    public function setConfig(array $config = array())
    {
        $this->request->username = (isset($config['username'])) ? $config['username'] : null;
        $this->request->password = (isset($config['password'])) ? $config['password'] : null;
        $host = (isset($config['host'])) ? $config['host'] : null;
        $this->host = 'https://' . $host . '/rest/api/2/';
    }

    public function testLogin()
    {
        $user = $this->getUser($this->request->username);
        if (!empty($user) && $this->request->lastRequestStatus()) {
            return true;
        }

        return false;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function getUser($username)
    {
        $this->request->openConnect($this->host . 'user/search/?username=' . $username, 'GET');
        $this->request->execute();
        $user = $this->getDecodedApiResponse($this->request->getResponseBody());

        return $user;
    }

    public function getStatuses()
    {
        $this->request->openConnect($this->host . 'status', 'GET');
        $this->request->execute();
        $statuses = $this->getDecodedApiResponse($this->request->getResponseBody());
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
        if ($result = $this->getDecodedApiResponse($this->request->getResponseBody())) {
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
        if ($result = $this->getDecodedApiResponse($this->request->getResponseBody())) {
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
        $result = $this->getDecodedApiResponse($this->request->getResponseBody());
        if (isset($result->comments)) {
            return $result->comments;
        }

        return false;
    }

    /**
     * Execute the actual JQL query against the JIRA API.
     *
     * The maxResults is needed, otherwise JIRA only returns the default page size (50).
     * Here in the wrapper we default the maxResults to 500 to get much more results.
     *
     * @link https://developer.atlassian.com/display/JIRADEV/JIRA+REST+API+Example+-+Query+issues
     *
     * @param string $query      The JQL query
     * @param string $fields     A filter of comma separated fields, or null, in case we want all fields
     * @param int    $maxResults Number of returned results (by default 500)
     * @return mixed False in case of error, array of resultsets otherwise
     */
    public function queryIssue($query, $fields = null, $maxResults = 500)
    {
        $query = urlencode($query);
        $url   = $this->host . 'search?jql=' . $query;
        if (isset($fields)) {
            $url.= '&fields=' . $fields;
        }
        if (isset($maxResults)) {
            $url.= '&maxResults=' . $maxResults;
        }
        $this->request->OpenConnect($url);
        $this->request->execute();
        $result = $this->getDecodedApiResponse($this->request->getResponseBody());
        if (isset($result->issues)) {
            return $result->issues;
        }

        return false;
    }

    public function createIssue($json)
    {
        $this->request->openConnect($this->host . 'issue/', 'POST', $json);
        $this->request->execute();

        return $this->request->lastRequestStatus();
    }

    public function addAttachment($filename, $issueKey)
    {
        $this->request->openConnect($this->host . 'issue/' . $issueKey . '/attachments', 'POST', null, $filename);
        $this->request->execute();

        return $this->request->lastRequestStatus();
    }

    public function updateIssue($json, $issueKey)
    {
        $this->request->openConnect($this->host . 'issue/' . $issueKey, 'PUT', $json);
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

    public function getVersions($project)
    {
        $this->request->openConnect($this->host . 'project/' . $project . '/versions');
        $this->request->execute();

        $result = $this->getDecodedApiResponse($this->request->getResponseBody());
        if (is_array($result)) {
            return $result;
        }

        return false;
    }

    public function createVersion($json)
    {
        $this->request->openConnect($this->host . 'version/', 'POST', $json);
        $this->request->execute();

        return $this->request->lastRequestStatus();
    }

    public function getComponents($project)
    {
        $this->request->openConnect($this->host . 'project/' . $project . '/components');
        $this->request->execute();

        $result = $this->getDecodedApiResponse($this->request->getResponseBody());
        if (is_array($result)) {
            return $result;
        }

        return false;
    }

    public function createComponent($json)
    {
        $this->request->openConnect($this->host . 'component/', 'POST', $json);
        $this->request->execute();

        return $this->request->lastRequestStatus();
    }

    public function updateFilter($json, $filterId)
    {
        $filterId = intval($filterId);
        if ($data = json_decode($json)) {
            $this->request->openConnect($this->host . 'filter/' . $filterId . '?expand', 'PUT', $data);
            $this->request->execute();

            return $this->request->lastRequestStatus();
        }
        return false;
    }

    /**
     * @param string $responseBody JSON with response body
     * @return mixed Decoded JSON
     */
    private function getDecodedApiResponse($responseBody)
    {
        /**
         * workaround to json_decode(): integer overflow detected error
         * @see https://stackoverflow.com/questions/19520487/json-bigint-as-string-removed-in-php-5-5/27909889
         */
        $maxIntLength = strlen((string) PHP_INT_MAX) - 1;
        $safeJson = preg_replace('/:\s*(-?\d{'.$maxIntLength.',})/', ': "$1"', $responseBody);
        $result = json_decode($safeJson);

        return $result;
    }
}
?>
