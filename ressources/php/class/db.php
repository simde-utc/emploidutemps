<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps'.'/ressources/mdp.php'); // Récupération des données

class DB extends PDO {
  public function __construct () {
    try { parent::__construct('mysql'.':host='.DB_HOST.'; dbname='.DB_NAME.'; charset=utf8', DB_USER, DB_PASS, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC)); }
    catch (PDOException $e)  { DB::meurt('__construct', $e); }
	}

	public function execute($s, $p)	{
		try { return $s->execute($p); }
		catch (PDOException $e) { DB::meurt('execute', $e); }
	}

	public function exec($p) {
		try { return parent::exec($p); }
		catch (PDOException $e) { DB::meurt('exec', $e); }
	}

	public function query($p)	{
		try { return parent::query($p); }
		catch (PDOException $e) { DB::meurt('query', $e); }
	}

  public function request($req, $args = array(), $types = array()) {
		try {
      $query = parent::prepare($req);

      if ($args != array()) {
        foreach ($args as $key => $arg) {
          if (isset($types[$key+1]))
            $query->bindParam($key+1, $args[$key], $types[$key+1]);
          else
            $query->bindParam($key+1, $args[$key]);
        }
      }

      $query->execute();

      return $query;
    }
		catch (PDOException $e) { DB::meurt('request', $e); }
  }

	private static function meurt($type, PDOException $e)	{
		$signature = date('Y/m/d H:i:s').'	'.$_SERVER['REMOTE_ADDR'];
    mail('samy.nastuzzi@etu.utc.fr', 'Erreur dans la DB', $signature.'	'.$type.'	'.$e->getMessage());
    echo $signature.'	'.$type.'	'.$e->getMessage();
		die('Une erreur a été détectée et a été signalée.');
	}
}
?>
