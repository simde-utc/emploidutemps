<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/include.php');
  header('Content-Type: application/json');

  if (isset($_GET['idUV']) && is_string($_GET['idUV']) && isset($_GET['color']) && is_string($_GET['color'])) {
    if ($_GET['color'] == 'NULL')
      $color = NULL;
    else
      $color = '#'.$_GET['color'];

    $db->request(
      'UPDATE uvs_followed SET color = ? WHERE login = ? AND idUV = ?',
      array($color, $_SESSION['login'], $_GET['idUV'])
    );
    echo json_encode(array('success' => 'Couleur changée avec succès'));
  }
  elseif (isset($_GET['idEvent']) && is_string($_GET['idEvent']) && isset($_GET['color']) && is_string($_GET['color'])) {
    if ($_GET['color'] == 'NULL')
      $color = NULL;
    else
      $color = '#'.$_GET['color'];

    $db->request(
      'UPDATE events_followed SET color = ? WHERE login = ? AND idEvent = ?',
      array($color, $_SESSION['login'], $_GET['idEvent'])
    );
    echo json_encode(array('success' => 'Couleur changée avec succès'));
  }
  elseif (isset($_GET['defaultMode']) && is_string($_GET['defaultMode'])) {
    if ($_GET['defaultMode'] == '' || $_GET['defaultMode'] == 'classique' || $_GET['defaultMode'] == 'comparer' || $_GET['defaultMode'] == 'modifier' || $_GET['defaultMode'] == 'semaine' || $_GET['defaultMode'] == 'organiser') {
      $db->request(
        'UPDATE students SET mode = ? WHERE login = ?',
        array($_GET['defaultMode'], $_SESSION['login'])
      );

      $_SESSION['mode'] = $_GET['defaultMode'];

      echo json_encode(array('success' => 'Mode par défaut affecté avec succès. Il s\'affichera à chaque fois que tu chargeras la page'));
    }
    else
      echo json_encode(array('error' => 'Mauvais mode choisi'));
  }
  else
    echo json_encode(array('error' => 'Aucune info donnée'));
?>
