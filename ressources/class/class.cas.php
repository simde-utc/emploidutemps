<?php
include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/class/class.xmlToArrayParser.php');

class CAS
{
	const URL = 'https://cas.utc.fr/cas/';


	public static function authenticate()	{
		if (!isset($_GET['ticket']) || empty($_GET['ticket']))
			return -1;

		$data = file_get_contents(self::URL.'serviceValidate?service=https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'&ticket='.$_GET['ticket']);

		if (empty($data))
			return -1;

		$parsed = new xmlToArrayParser($data);

		if (!isset($parsed->array['cas:serviceResponse']['cas:authenticationSuccess']))
			return -1;

		return $parsed->array['cas:serviceResponse']['cas:authenticationSuccess'];
	}


	public static function login() {
		header('Location: '.self::URL.'login?service=https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
	}


	public static function logout()	{
		header('Location: '.self::URL.'logout?service=https://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']));//ou SCRIPT_NAME?
		// On n'utilise pas REQUEST_URI sinon cela d�connecterait � l'infini.
	}
}
