<?php session_start();

  if (isset($_GET['idUV']) && is_string($_GET['idUV']) && isset($_GET['color']) && is_string($_GET['color'])) {
    $login = $_SESSION['login'];
    $idUV = $_GET['idUV'];
    if ($_GET['color'] == 'NULL')
      $color = NULL;
    else
      $color = '#'.$_GET['color'];

    include($_SERVER['DOCUMENT_ROOT'].'/ressources/class/class.bdd.php');
    $bdd = new BDD();

    $query = $bdd->prepare('UPDATE cours SET color = ? WHERE login = ? AND id = ?;');

    $bdd->execute($query, array($color, $login, $idUV));
  }
  header('Location: /');
  exit;
?>
