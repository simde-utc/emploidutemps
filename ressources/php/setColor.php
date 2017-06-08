<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/include.php');
  header('Content-Type: application/json');

  if (isset($_GET['idUV']) && is_string($_GET['idUV']) && isset($_GET['color']) && is_string($_GET['color'])) {
    if ($_GET['color'] == 'NULL')
      $color = NULL;
    else
      $color = '#'.$_GET['color'];

    $query = $bdd->prepare('UPDATE uvs_followed SET color = ? WHERE login = ? AND idUV = ?');

    $bdd->execute($query, array($color, $_SESSION['login'], $_GET['idUV']));
    echo json_encode(array('status' => 'ok'));
  }
  else
    header('HTTP/1.0 400 Bad Request');
?>
