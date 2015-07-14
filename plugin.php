<?php

class toggl extends SlackServicePlugin {

	public $name = 'Toggl Service';
	public $desc = "Log your day's time via Slack commands.";
	public $cfg = array('has_token' => true);

	private $botname = 'togglebot';

	function onView () {
		return $this->smarty->fetch('view.txt');
	}

	function onHook ($req) {

		try {
			list($duration, $description) = $this->parseInput($req['post']['text']);
		} catch (Exception $e) {
			$this->postError($e->getMessage(), $req['post']['channel_id']);
		}
	}

	private function parseInput ($input) {
		$words = explode(' ', $input, 3);

		// 3 pieces required: trigger, duration, and description
		if (count($words) < 3)
			throw new Exception('Duration or description not provided.');

		return array($words[1], $words[2]);
	}

	private function postError ($message, $channel) {
		$this->postToChannel($message, array(
			'channel' => $channel,
			'username' => $this->botname
		));
	}
}
