<?php

namespace Artskills\ZabbixSlackAlert;

class SlackAlert
{
	const SLACK_URL = 'https://slack.com/api/';
	const SLACK_SUCCESS_RESULT = 'ok';

	/**
	 * Токен
	 *
	 * @var null|string
	 */
	private $_slackToken = null;

	/**
	 * Имя бота
	 *
	 * @var null|string
	 */
	private $_slackUserName = null;

	/**
	 * SlackAlert constructor.
	 *
	 * @param string $slackToken
	 * @param string $slackUserName
	 */
	public function __construct($slackToken, $slackUserName)
	{
		$this->_slackToken = $slackToken;
		$this->_slackUserName = $slackUserName;
	}

	/**
	 * Отправка запроса в Slack
	 *
	 * @param string $channel В какой канал слать
	 * @param string $subject Тема сообщения
	 * @param string $message Тело сообщения
	 * @return bool
	 */
	public function send($channel, $subject, $message)
	{
		$sendData = [
			'type' => 'message',
			'channel' => $channel,
			'as_user' => false,
			'username' => $this->_slackUserName,
			'text' => $subject . "\n" . $message,
		];

		$evenId = $this->_getEventId($message);
		if ($evenId !== null) {
			$workDir = ROOT . '/tmp';
			$eventFile = $workDir . '/' . $evenId . '.json';
			if (is_file($eventFile) && ($threadPost = json_decode(file_get_contents($eventFile), true))) {
				$newThread = false;
				$sendData['thread_ts'] = $threadPost['ts'];
			} else {
				$newThread = true;
			}
		}

		$result = $this->_sendData('chat.postMessage', $sendData);

		if ($result[static::SLACK_SUCCESS_RESULT] && $evenId !== null) {
			if ($newThread) {
				file_put_contents($eventFile, json_encode($result));
			} else {
				if (substr($subject, 0, 3) === 'OK:' && is_file($eventFile)) {
					unlink($eventFile);
				}
			}
		}
		return true;
	}

	/**
	 * Ищем ID события
	 *
	 * @param string $message
	 * @return string|null
	 */
	private function _getEventId($message)
	{
		if (preg_match('/Original event ID:\s([0-9]+)\s?/m', $message, $matches)) {
			return $matches[1];
		} else {
			return null;
		}
	}

	/**
	 * Отправка запроса в Slack
	 *
	 * @param string $method
	 * @param array $data
	 * @return array
	 */
	private function _sendData($method, array $data)
	{
		$data['token'] = $this->_slackToken;
		$result = file_get_contents(static::SLACK_URL . $method . '?' . http_build_query($data));
		return json_decode($result, true);
	}
}