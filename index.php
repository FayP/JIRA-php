<?php
ini_set('display_errors', 'On');
require_once('class.jira.php');

$config->username= "USERNAME";
$config->password= "PASSWORD";
$config->port= 443;
$config->host= "DOMAIN";
$project = "KEY";


$data ->fields->project->key='TTP';
$data ->fields->summary='testing';
$data ->fields->description="decription";
$data ->fields->issuetype->name='QA Check';

$summary->set = 'update to TTP-20, through update method';
$update->update->summary= array($summary);

$query->assignee = 'faypickering';
$query->project = 'USSF';

//$newcase = new Jira($config);
//$newcase->createIssue($data);

//$updateCase = new Jira($config);
//$updateCase->updateIssue($update, $project.'-18');

$queryIssue = new Jira($config);
$queryIssue->queryIssue($query);

//$attachment = new Jira($config);
//$attachment->addAttachment('./someFile.jpg', $project.'-18');
?>
