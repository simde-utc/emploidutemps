<?php $api = TRUE;
  include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps'.'/ressources/php/include.php');
/*
  if (!isset($_SERVER['HTTP_'.HEADER_PICBOT])) {
    header('HTTP/1.0 403 Forbidden');
    exit;
  }
*/

  if (!isset($_GET['login'])) {
    header('HTTP/1.0 401 Unauthorized');
    exit;
  }

  echo json_encode(isEtu($_GET['login']) ? getNextCours($_GET['login'], date('Y-m-d')) : array('error' => 'inconnu'));
?>
