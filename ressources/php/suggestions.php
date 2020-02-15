<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/include.php');
  include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/functions/suggestions.php');

  header('Content-Type: application/json');

  /*  TRAITEMENT  */
  echo json_encode(getStudentInfosListFromSearch());
?>
