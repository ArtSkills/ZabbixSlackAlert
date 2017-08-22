#!/usr/bin/env php
<?php
define('ROOT', __DIR__ . '/..');

$configFile = ROOT . '/config/config.inc';
require $configFile;
require ROOT . '/src/SlackAlert.php';

$alert = new \Artskills\ZabbixSlackAlert\SlackAlert(SLACK_ACCESS_TOKEN, 'Slack test bot');

$channel = '#zabbix';
$subject1 = 'PROBLEM: "DirectSmile Generator.exe" doesn\'t run DSM1';
$message1 = 'Trigger: "DirectSmile Generator.exe" doesn\'t run DSM1
Trigger status: PROBLEM
Trigger severity: High
Trigger URL:

Item values:
1. Number of "DirectSmile Generator.exe" processes (DSM1:proc.num[DirectSmile Generator.exe]): 0

Original event ID: 20544';

print 'Send first event ' . ($alert->send($channel, $subject1, $message1) === true ? 'Success' : 'Fail') . "\n";

sleep(1);

$subject2 = 'OK: "DirectSmile Generator.exe" doesn\'t run DSM1';
$message2 = 'Trigger: "DirectSmile Generator.exe" doesn\'t run DSM1
Trigger status: OK
Trigger severity: High
Trigger URL:

Item values:

1. Number of "DirectSmile Generator.exe" processes (DSM1:proc.num[DirectSmile Generator.exe]): 1

Original event ID: 20544';

print 'Send second event ' . ($alert->send($channel, $subject2, $message2) === true ? 'Success' : 'Fail') . "\n";