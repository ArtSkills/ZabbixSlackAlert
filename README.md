# Zabbix to Slack channel alert notificator
It is a simple PHP script that uses the custom alert script functionality within [Zabbix](https://www.zabbix.com/documentation/3.0/manual/config/notifications/media/script) along with the Slack [Web Api](https://api.slack.com/methods) 

## Requirements
* PHP greater than 5.4.
* Default zabbix notification messages. 

## Features
* Message threading by event ID.
* Icons by event priority.
* Add reaction icon for resolved alerts.

## Installation
* Download project
* Make symbolic link to ``bin/sendSlackAlert.php`` to your ``AlertScriptsPath`` directory (that is specified within the Zabbix servers' configuration file ``zabbix_server.conf``).

## Configuration
* Copy ``config/config.inc-sample`` to ``config/config.inc`` file.
* Create a new bot: https://<your team>.slack.com/apps/new/A0F7YS25R-bots
	* Invite bot for private channels.
	* Place "OAuth Access Token" to ``SLACK_ACCESS_TOKEN`` constant.
	* Fill ``SLACK_USER_NAME`` constant.
	* Fill ``ZABBIX_FRONTEND_URL`` constant.
* Add new media type: Administration -> Create media type:
	* Name: ``Slack``
	* Type: ``Script``
	* ScriptName: ``sendSlackAlert.php`` (our symlink)
	* Script parameters:
		* ``{ALERT.SENDTO}``
		* ``{ALERT.SUBJECT}``
		* ``{ALERT.MESSAGE}``
	* Enabled: ``true``
* Add media to user (Media tab):
	* Type: ``Slack``
	* Send to: <Zabbix channel name>, e.x. ``#zabbix`` 
	* When active: ``1-7,00:00-24:00``
	* All other flag are default
	* Enabled: ``true``
* Add new action: Configuration -> Actions
	* Action tab by default
	* Conditions tab by default
	* Operations tab -> Action operations -> new:
	 	* Send only to: ``Slack``
	 	* Add user to ``Send to Users`` block
	 	* Push ``add`` link
	* Push ``add`` action button.
* Enjoy it!

If you have any questions, please write to tune@artskills.ru