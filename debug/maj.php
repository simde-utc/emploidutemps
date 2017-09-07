<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps'.'/ressources/php/include.php');
  include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps'.'/ressources/php/class/update.php');

  $dir = $_SERVER['DOCUMENT_ROOT'].'/emploidutemps'.'/ressources/edt';

  if (!$_SESSION['admin']) {
    echo 'Tu n\'as pas les droits';
    exit;
  }
  if (UPDATE::checkModcasid($curl)) {
    if (UPDATE::tryToUpdate($curl))
      header('Location: /emploidutemps/');
    else
      echo '<script type="text/javascript">function refresh() { window.location.href=window.location.href } setTimeout("refresh()", 250);</script>';
  }
  else
    echo 'Veuillez entrer votre MODCASID';
