#!/usr/bin/env php
<?php
define('ROOT', __DIR__ . '/..');

$configFile = ROOT . '/config/config.inc';
if (!is_file($configFile)) {
	die("Create configuration file $configFile\n");
}
require $configFile;
require ROOT . '/src/SlackAlert.php';

if ($argc !== 4 && $argc !== 5) {
	die("Usage: /sendSlackAlert.php <Channel> <Subject> <Message> [<eventId>]\n");
}

$alert = new \Artskills\ZabbixSlackAlert\SlackAlert(SLACK_ACCESS_TOKEN, SLACK_USER_NAME);
print $alert->send($argv[1], $argv[2], $argv[3], $argc > 4 ? $argv[4] : null) . "\n";