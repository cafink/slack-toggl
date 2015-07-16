<?php

class toggl extends SlackServicePlugin {

	public $name = 'Toggl Service';
	public $desc = "Log your day's time via Slack commands.";
	public $cfg = array('has_token' => true);

	private $botname = 'togglbot';
	private $toggl_userpass = '{api_token}:api_token';

	function onView () {
		return $this->smarty->fetch('view.txt');
	}

	function onHook ($req) {

		try {
			$command = $this->removeTrigger($req['post']['text'], $req['post']['trigger_word']);
			list($duration, $description) = $this->parseCommand($command);
			$this->createTimeEntry($duration, $description);
			$this->postSuccess($req['post']['channel_id']);
		} catch (Exception $e) {
			$this->postError($e->getMessage(), $req['post']['channel_id']);
		}
	}

	private function removeTrigger ($input, $trigger) {
		return trim(substr($input, strlen($trigger)));
	}

	private function parseCommand ($input) {

		$words = explode(' ', $input, 2);

		// 2 pieces required: duration, and description
		if (count($words) < 2)
			throw new Exception('Duration or description not provided.');
		elseif (!ctype_digit ($words[0]))
			throw new Exception('Duration must be an integer.');

		return array($words[0], $words[1]);
	}

	private function postError ($message, $channel) {
		$this->postToChannel('Error: ' . $message, array(
			'channel' => $channel,
			'username' => $this->botname
		));
	}

	private function postSuccess ($channel) {
		$this->postToChannel('Time entry created successfully.', array(
			'channel' => $channel,
			'username' => $this->botname
		));
	}

	private function createTimeEntry ($duration, $description) {
		return $this->sendRequest('time_entries', array(
			'time_entry' => array(
				'description' => $description,
				'duration' => $duration,
				'start' => date('c'), // ISO 8601
				'created_with' => 'Slack'
			)
		));
	}

	private function sendRequest ($url, $params) {

		$data = json_encode($params);

		$headers = array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen($data),
			'Authorization: Basic ' . base64_encode($this->toggl_userpass),
		);

		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => "https://www.toggl.com/api/v8/{$url}",
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
