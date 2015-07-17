<?php

class toggl extends SlackServicePlugin {

	public $name = 'Toggl Service';
	public $desc = "Log your day's time via Slack commands.";
	public $cfg = array('has_token' => true);

	private $toggl_url = 'https://www.toggl.com/api/v8/';
	private $toggl_userpass = '{api_token}:api_token';
	private $channel = '#general';
	private $botname = 'togglbot';

	private $commands = array('create');

	function onView () {
		return $this->smarty->fetch('view.txt');
	}

	function onHook ($req) {

		$this->channel = $req['post']['channel_id'];

		try {
			$full_command = $this->removeTrigger($req['post']['text'], $req['post']['trigger_word']);
			list($command, $args) = $this->parseCommand($full_command);

			if (in_array($command, $this->commands)) {
				call_user_func(array($this, "{$command}TimeEntry"), $args);
				$this->postSuccess();
			} else {
				$this->postError("Command \"{$command}\" not recognized.");
			}
		} catch (Exception $e) {
			$this->postError($e->getMessage());
		}
	}

	private function removeTrigger ($input, $trigger) {
		return trim(substr($input, strlen($trigger)));
	}

	private function parseCommand ($input) {

		if (empty($input))
			throw new Exception('No command specified.');

		// 2 pieces: command and arguments
		$words = explode(' ', $input, 2);

		return array($words[0], $words[1]);
	}

	private function postError ($message) {
		$this->postToChannel('Error: ' . $message, array(
			'channel' => $this->channel,
			'username' => $this->botname
		));
	}

	private function postSuccess () {
		$this->postToChannel('Time entry created successfully.', array(
			'channel' => $this->channel,
			'username' => $this->botname
		));
	}

	private function parseCreateArgs ($args) {

		if (empty($args))
			throw new Exception('Duration must be supplied.');

		// First "word" is duration; remainder is description
		$args = explode(' ', $args, 2);

		if (!ctype_digit ($args[0]))
			throw new Exception('Duration must be an integer.');

		return $args;
	}

	private function createTimeEntry ($args) {

		list($duration, $description) = $this->parseCreateArgs($args);

		return $this->sendRequest('time_entries', array(
			'time_entry' => array(
				'description' => $description,
				'duration' => $duration,
				'start' => date('c'), // ISO 8601
				'created_with' => 'Slack'
			)
		));
	}

	private function sendRequest ($request_url, $params) {

		$data = json_encode($params);

		$headers = array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen($data),
			'Authorization: Basic ' . base64_encode($this->toggl_userpass)
		);

		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => $this->toggl_url . $request_url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
			CURLOPT_USERPWD => $this->toggl_userpass,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_CAINFO => __DIR__ . '/cacert.pem', // Bundle of CA Root Certificates
			CURLOPT_POSTFIELDS => $data
		));

		$results = curl_exec($curl);
		$info = curl_getinfo($curl);
		//$error = curl_error($curl);

		curl_close($curl);

		// There was an error
		if ($info['http_code'] != 200)
			throw new Exception('Unable to create time entry.');

		return $results;
	}
}
