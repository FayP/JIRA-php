<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/ 
 *
 * @filesource	getIssue.test.class.php
 * @author		Francisco Mancardi (francisco.mancardi@gmail.com)
 *
 * @internal revisions
 *
 **/
require_once('../src/JiraApi/RestRequest.php');
require_once('../src/JiraApi/Jira.php');

$settings = array('host' => 'https://testlink.atlassian.net/rest/api/latest/',
                  'username' => 'testlink.forum', 'password' => 'forum');

$api = new JiraApi\Jira($settings);
$issueKey = 'ZOFF-2';
$out = $api->getIssue($issueKey);
echo 'Test - Get Issue ' . $issueKey . '<br>'; 
echo '<pre>';
var_dump($out);
echo '</pre>';