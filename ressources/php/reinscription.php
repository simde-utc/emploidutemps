<?php include($_SERVER['DOCUMENT_ROOT'].'/ressources/php/include.php');
  $query = $GLOBALS['bdd']->prepare('UPDATE etudiants SET desinscrit = 0 WHERE login = ?');
  $GLOBALS['bdd']->execute($query, array($_SESSION['login']));

  echo '<div style="background-color: #00FF00" id="popupHead">Vous avez été réinscrit au service pour ce semestre avec succès !</div>';
?>
