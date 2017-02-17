<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/mdp.php'); // Récupération des données

  include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/ginger/ApiException.php');
  include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/ginger/KoalaClient.php');
  include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/ginger/GingerClient.php');

  $ginger = new GingerClient(GINGER_KEY);
?>
