<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps'.'/ressources/php/include_api.php');
  api(array(PICBOT_APIKEY));

  echo json_encode(isEtu($_GET['login']) ? getNextCours($_GET['login'], date('Y-m-d')) : array('error' => 'inconnu'));
?>
