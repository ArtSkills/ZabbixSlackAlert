<?php

namespace Artskills\ZabbixSlackAlert;

class SlackAlert
{
	const SLACK_URL = 'https://slack.com/api/';
	const SLACK_SUCCESS_RESULT = 'ok';

	const SEARCH_EVENT_PATTERN = '/Original event ID\:\s([0-9]+)\s?/m';
	const SUCCESS_MESSAGE_PREFIX = 'OK:';

	const ICON_LIST = [
		'Default' => ':information_source:',
		'Information' => ':information_source:',
		'Warning' => ':warning:',
		'Average' => ':exclamation:',
		'High' => ':bangbang:',
		'Disaster' => ':no_entry:',
		'OK' => ':shamrock:',
	];

	const DIR_ACTIVE = 'active';
	const DIR_RESOLVED = 'resolved';

	const SUCCESS_REACTION_ICON = 'white_check_mark';

	const CLEAN_SUCCESS_MESSAGES_TIMEOUT = '-1 day';

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

		if (!is_dir(ROOT . '/tmp/' . static::DIR_ACTIVE)) {
			mkdir(ROOT . '/tmp/' . static::DIR_ACTIVE);
		}

		if (!is_dir(ROOT . '/tmp/' . static::DIR_RESOLVED)) {
			mkdir(ROOT . '/tmp/' . static::DIR_RESOLVED);
		}
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
			'text' => '*' . $subject . "*\n" . preg_replace(static::SEARCH_EVENT_PATTERN, 'Original event ID: <' . ZABBIX_FRONTEND_URL . '|$1>', $message),
			'icon_emoji' => $this->_detectMessageIcon($subject, $message),
		];

		$evenId = $this->_getEventId($message);
		if ($evenId !== null) {
			$workDir = ROOT . '/tmp';
			$eventFile = $workDir . '/' . static::DIR_ACTIVE . '/' . $evenId . '.json';
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
				if ($this->_isSuccessMessage($subject) && is_file($eventFile)) {
					$this->_addSuccessReaction($threadPost);
					$this->_clearOldMessages();
					$resolvedFile = $workDir . '/' . static::DIR_RESOLVED . '/' . $evenId . '.json';
					if (is_file($resolvedFile)) {
						unlink($resolvedFile);
					}
					rename($eventFile, $resolvedFile);
				}
			}
		}
		return $result[static::SLACK_SUCCESS_RESULT];
	}

	/**
	 * Добавляем пометку о том, что проблема решена
	 *
	 * @param array $threadPost
	 * @return bool
	 */
	private function _addSuccessReaction($threadPost)
	{
		$result = $this->_sendData('reactions.add', [
			'channel' => $threadPost['channel'],
			'timestamp' => $threadPost['ts'],
			'name' => static::SUCCESS_REACTION_ICON,
		]);

		return $result[static::SLACK_SUCCESS_RESULT];
	}

	/**
	 * Ищем ID события
	 *
	 * @param string $message
	 * @return string|null
	 */
	private function _getEventId($message)
	{
		if (preg_match(static::SEARCH_EVENT_PATTERN, $message, $matches)) {
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

	/**
	 * Определяем иконку сообщения
	 *
	 * @param string $subject
	 * @param string $message
	 * @return string
	 */
	private function _detectMessageIcon($subject, $message)
	{
		if ($this->_isSuccessMessage($subject)) {
			return static::ICON_LIST['OK'];
		} else {
			if (preg_match('/Trigger severity\:\s([A-Za-z]+)\s/', $message, $matches)) {
				if (array_key_exists($matches[1], static::ICON_LIST)) {
					return static::ICON_LIST[$matches[1]];
				}
			}
			return static::ICON_LIST['Default'];
		}
	}

	/**
	 * Является ли данное сообщение уведомлением о решении проблемы
	 *
	 * @param string $subject
	 * @return bool
	 */
	private function _isSuccessMessage($subject)
	{
		return substr($subject, 0, 3) === static::SUCCESS_MESSAGE_PREFIX;
	}

	/**
	 * Чистим старые успешные уведомления
	 */
	private function _clearOldMessages()
	{
		$dirPath = ROOT . '/tmp/' . static::DIR_RESOLVED;
		$workDir = dir($dirPath);
		$timeout = strtotime(self::CLEAN_SUCCESS_MESSAGES_TIMEOUT);
		while (false !== ($entry = $workDir->read())) {
			$workFile = $dirPath . '/' . $entry;
			if (is_file($workFile) && filemtime($workFile) <= $timeout) {
				unlink($workFile);
			}
		}
		$workDir->close();
	}
}