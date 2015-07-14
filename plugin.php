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
			list($duration, $description) = $this->parseInput($req['post']['text']);
			$this->createTimeEntry($duration, $description);
			$this->postSuccess($req['post']['channel_id']);
		} catch (Exception $e) {
			$this->postError($e->getMessage(), $req['post']['channel_id']);
		}
	}

	private function parseInput ($input) {
		$words = explode(' ', $input, 3);

		// 3 pieces required: trigger, duration, and description
		if (count($words) < 3)
			throw new Exception('Duration or description not provided.');
		elseif (!ctype_digit ($words[1]))
			throw new Exception('Duration must be an integer.');

		return array($words[1], $words[2]);
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

		$data = array(
			'time_entry' => array(
				'description' => $description,
				'duration' => $duration,
				'start' => date('c'), // ISO 8601
				'created_with' => 'Slack'
			)
		);

		$data_json = json_encode($data);

		$headers = array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen($data_json),
			'Authorization: Basic ' . base64_encode($this->toggl_userpass),
		);

		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => 'https://www.toggl.com/api/v8/time_entries',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
			CURLOPT_USERPWD => $this->toggl_userpass,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_CAINFO => __DIR__ . '/cacert.pem', // Bundle of CA Root Certificates
			CURLOPT_POSTFIELDS => $data_json
		));

		curl_exec($curl);
		$info = curl_getinfo($curl);
		//$error = curl_error($curl);

		curl_close($curl);

		// There was an error
		if ($info['http_code'] != 200)
			throw new Exception('Unable to create time entry.');
	}
}
