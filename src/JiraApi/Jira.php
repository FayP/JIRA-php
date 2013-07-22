<?php

namespace JiraApi;

class Jira
{

    protected $host;

    public function __construct(array $config = array())
    {
        $this->request = new RestRequest();
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

    public function getUser($username)
    {
        $this->request->openConnect($this->host . 'user/search/?username=' . $username, 'GET');
        $this->request->execute();
        $user = json_decode($this->request->getResponseBody());

        return $user;
    }

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
}
?>
