<?php

class BDD extends PDO {
  const DB_TYPE = 'mysql';
  const DB_HOST = 'localhost';
  const DB_NAME = 'emploidutemps';
  const DB_USER = 'emploidutemps';
  const DB_PASS = 'Il y a une vie après les cours';
  const DB_OPTION = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC);

  public function __construct () {
    try { parent::__construct($this::DB_TYPE.':host='.$this::DB_HOST.'; dbname='.$this::DB_NAME.'; charset=utf8', $this::DB_USER, $this::DB_PASS, $this::DB_OPTION); }
    catch (PDOException $e)  { BDD::meurt('__construct', $e); }
	}

	public function execute($s, $p)	{
		try { return $s->execute($p); }
		catch (PDOException $e) { BDD::meurt('execute', $e); }
	}

	public function exec($p) {
		try { return parent::exec($p); }
		catch (PDOException $e) { BDD::meurt('exec', $e); }
	}

	public function query($p)	{
		try { return parent::query($p); }
		catch (PDOException $e) { BDD::meurt('query', $e); }
	}

	private static function meurt($type, PDOException $e)	{
		$signature = date('Y/m/d H:i:s').'	'.$_SERVER['REMOTE_ADDR'];
		file_put_contents($_SERVER['DOCUMENT_ROOT'].'/logs/bdd.exception.txt',$signature.'	'.$type.'	'.$e->getMessage()."\r\n", FILE_APPEND);
		die('SQL Error loged : '.$signature);
	}
}
?>
