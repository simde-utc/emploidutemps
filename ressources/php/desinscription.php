<?php include($_SERVER['DOCUMENT_ROOT'].'/ressources/php/include.php');
  $query = $GLOBALS['bdd']->prepare('UPDATE etudiants SET desinscrit = 1 WHERE login = ?');
  $GLOBALS['bdd']->execute($query, array($_SESSION['login']));

  echo '<div style="background-color: #FF0000" id="popupHead">Vous avez été désinscrit du service pour ce semestre avec succès !</div>';
?>
