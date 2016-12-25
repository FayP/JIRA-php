<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/ 
 *
 * @filesource	getCreateIssueMetadata.test.class.php
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

/*
$out = $api->getCreateIssueMetadata();
echo 'Test - Get Create Issue Metadata for all projects' . '<br>'; 
echo '<pre>';
var_dump($out);
echo '</pre>';
*/

$tg = 'ZOFF';
$out = $api->getCreateIssueMetadata($tg);
echo 'Test - Get Create Issue Metadata for project: ' . $tg . '<br>'; 
echo '<pre>';
var_dump($out);
echo '</pre>';

/*
$tg = 'ZOFF,SCRUM20NOV';
$out = $api->getCreateIssueMetadata($tg);
echo 'Test - Get Create Issue Metadata for projects: ' . $tg . '<br>'; 
echo '<pre>';
var_dump($out);
echo '</pre>';


$tg = 'ZOFF,SCRUM20NOV';
$out = $api->getCreateIssueFields($tg);
echo 'Test - Get Create Issue Fields for project: ' . $tg . '<br>'; 
echo '<pre>';

var_dump($out);
echo '</pre>';
*/
