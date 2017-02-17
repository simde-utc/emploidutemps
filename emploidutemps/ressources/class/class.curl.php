<?php
class CURL
{
	private $ch;

	public function __construct($proxy)
	{
		$this->ch = curl_init();
		curl_setopt($this->ch, CURLOPT_HEADER, false);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($this->ch, CURLOPT_SSLVERSION, 3);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
		//hcurl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 2);

		if ($proxy)
		{
			curl_setopt($this->ch, CURLOPT_PROXY, 'proxyweb.utc.fr');
			curl_setopt($this->ch, CURLOPT_PROXYPORT, '3128');
			//curl_setopt($ch, CURLOPT_PROXYTYPE, 'HTTP'); // Facultatif ?
		}
	}


	public function setCookies($cookies)
	{
		curl_setopt($this->ch, CURLOPT_COOKIE, $cookies);
	}


	public function get($url)
	{
		curl_setopt($this->ch, CURLOPT_URL, $url);
		return curl_exec($this->ch);
	}


	public function post($url, $params)
	{
		curl_setopt($this->ch, CURLOPT_URL, $url);
		curl_setopt($this->ch, CURLOPT_POST, true);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $params);
		return curl_exec($this->ch);
	}

	
	public function error()
	{
		return curl_error($this->ch);
	}
}
?>
