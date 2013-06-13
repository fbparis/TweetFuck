<?php
/*
 * Twitter API without OAuth (rate limit = 1000 reqs/hour)
 */
class TweetFuck {
	public $host = 'https://api.twitter.com/1/';
	public $curl_opts = array();
	public $curl_opts_api = array();
	public $lastUrl = '';
	public $lastResponseInfo = array();
	public $lastResponseCode = 0;
	public $lastResponse = '';	
	public $cookie_file = '';
	public $authenticity_token = '';

	function __construct($cookie_file=null) {
		$this->cookie_file = !$cookie_file ? $_SERVER['PATH_TRANSLATED'] . '.cookies.txt' : $cookie_file;
		$context_options = stream_context_get_options(stream_context_get_default());
		if (@$context_options['socket']['bindto']) $this->curl_opts[CURLOPT_INTERFACE] = $context_options['socket']['bindto'];
		$this->curl_opts[CURLOPT_SSL_VERIFYPEER] = false;
		$this->curl_opts[CURLOPT_RETURNTRANSFER] = true;
		$this->curl_opts[CURLOPT_COOKIEFILE] = $this->cookie_file;
		$this->curl_opts[CURLOPT_COOKIEJAR] = $this->cookie_file;
		$this->curl_opts[CURLOPT_FOLLOWLOCATION] = true;
		$this->curl_opts[CURLOPT_MAXREDIRS] = 1;
		$this->curl_opts_api[CURLOPT_HTTPHEADER] = array(
			'X-PHX: true'
		);
	}
	
	public function signin($username_or_email, $password) {
		if (file_exists($this->cookie_file)) unlink($this->cookie_file);
		if (!$response = $this->http_request('http://twitter.com/')) return false;
		if (!$this->authenticity_token = preg_match('#<input type="hidden" value="([^"]*)" name="authenticity_token">#s', $response, $m) ? $m[1] : '') return false;
		if ($response = $this->http_request('https://twitter.com/sessions',array('session[username_or_email]'=>$username_or_email,'session[password]'=>$password,'authenticity_token'=>$this->authenticity_token,'redirect_after_login'=>'/','remember_me'=>1))) {
			return preg_match('#^https?://twitter\.com/$#si', $this->lastUrl);
		}
		return false;
	}
	
	public function get($url, $parameters=array()) {
		return $this->api_call($url, 'GET', $parameters);
	}

	public function post($url, $parameters=array()) {
		$parameters['post_authenticity_token'] = $this->authenticity_token;
		return $this->api_call($url, 'POST', $parameters);
	}
	
	protected function api_call($url, $method, $parameters) {
		if (!preg_match('#^https?://#si',$url)) {
			$url = "{$this->host}{$url}.json";
		}
		switch ($method) {
			case 'GET':
				$response = $this->http_request($url . (@count($parameters) ? (strpos($url, '?') === false ? '?' : '&') . http_build_query($parameters) : ''), null, true);
				break;
			case 'POST':
				$response = $this->http_request($url, $parameters, true);
				break;
			default:
				return false;
		}
		return $this->lastResponseCode == 200 ? json_decode($response) : false;
	}
	
	protected function http_request($url, $parameters=null, $in_api=false) {
		$ch = curl_init($url);
		curl_setopt_array($ch, $this->curl_opts);
		if ($in_api) curl_setopt_array($ch, $this->curl_opts_api);
		if ($parameters !== null) {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
		}
		$this->lastResponse = curl_exec($ch);
		$this->lastResponseInfo = curl_getinfo($ch);
		$this->lastResponseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$this->lastUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
		curl_close($ch);
		return $this->lastResponse;
	}
}
?>