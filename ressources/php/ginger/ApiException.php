<?php

class ApiException extends \Exception {
	static $http = array (
		60 => "Fichier de certification SSL manquant pour la connexion",
		77 => "Fichier de certification SSL introuvable ou incorrect",
		100 => "Continue",
		101 => "Switching Protocols",
		200 => "OK",
		201 => "Created",
		202 => "Accepted",
		203 => "Non-Authoritative Information",
		204 => "No Content",
		205 => "Reset Content",
		206 => "Partial Content",
		300 => "Multiple Choices",
		301 => "Moved Permanently",
		302 => "Found",
		303 => "See Other",
		304 => "Not Modified",
		305 => "Use Proxy",
		307 => "Temporary Redirect",
		400 => "Bad Request",
		401 => "Non autorisé : une clé d'accès est nécessaire pour exécuter cette requête",
		402 => "Payment Required",
		403 => "Interdit : l'authentification est refusée",
		404 => "Non trouvé : la ressource demandée n'existe pas",
		405 => "Method Not Allowed",
		406 => "Not Acceptable",
		407 => "Proxy Authentication Required",
		408 => "Request Time-out",
		409 => "Conflict",
		410 => "Gone",
		411 => "Length Required",
		412 => "Precondition Failed",
		413 => "Request Entity Too Large",
		414 => "Request-URI Too Large",
		415 => "Unsupported Media Type",
		416 => "Requested range not satisfiable",
		417 => "Expectation Failed",
		500 => "Internal Server Error",
		501 => "Not Implemented",
		502 => "Bad Gateway",
		503 => "Service Unavailable",
		504 => "Gateway Time-out"
	);

	public function __construct($code, $message = null, \Exception $previous = null) {
		$info = $this::$http[$code];

		if (!is_null($message)) {
			$info .= sprintf('. Message: %s', $message);
		}

		parent::__construct($info, $code, $previous);
		$this->code = $code;
	}
}
