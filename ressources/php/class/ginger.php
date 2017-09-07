<?php

  include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps'.'/ressources/php/ginger/ApiException.php');
  include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps'.'/ressources/php/ginger/KoalaClient.php');
  include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps'.'/ressources/php/ginger/GingerClient.php');

  $ginger = new GingerClient(GINGER_KEY);
?>
