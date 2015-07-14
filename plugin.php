<?php

class toggl extends SlackServicePlugin {

	public $name = 'Toggl Service';
	public $desc = "Log your day's time via Slack commands.";

	public $cfg = array('has_token' => true);

	function onView () {
		return $this->smarty->fetch('view.txt');
	}

	function onHook ($req) {

		$this->postToChannel('Hello, world!', array(
			'channel'   => '#test',
			'username' => 'togglbot'
		));
	}
}
