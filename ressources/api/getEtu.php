<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps'.'/ressources/php/include_api.php');
  api(array(IC05_APIKEY));

  echo json_encode(isset($_GET['login']) ? (isEtu($_GET['login']) ? array('etu' => getEtu($_GET['login'])) : array('error' => 'inconnu')) : array('etus' => getEtu()));
?>
