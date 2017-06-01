<?php $api = TRUE;
  include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps'.'/ressources/php/include.php');

  function api($authorized_api) {
    if (!isset($_GET['api_key'])) {
      header('HTTP/1.0 403 Forbidden');
      exit;
    }
    else if (!in_array($_GET['api_key'], $authorized_api)) {
      header('HTTP/1.0 401 Unauthorized');
      exit;
    }
    else
      header('Content-Type: application/json');
  }
