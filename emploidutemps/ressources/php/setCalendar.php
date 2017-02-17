<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/include.php');

  function send($status, $info) {
    echo '<div style="background-color: '.($status == 'error' ? '#FF0000': '#00FF00').'" id="popupHead">'.$info.'</div>';
    exit;
  }

  function addValue($query, $array) {
    $test = $GLOBALS['bdd']->prepare('SELECT jour FROM jours WHERE jour = ?');
    $GLOBALS['bdd']->execute($test, array($array[0]));

    if ($test->rowCount() == 0) {
      $GLOBALS['bdd']->execute($query, $array);
      send('success', 'Jour ajouté avec succès !');
    }
    else
      send('error', 'Impossible d\'ajouter l\'information donnée, peut-être déjà existante ?');
  }

  if (isset($_GET['jour']) && isset($_GET['mois']) && isset($_GET['annee']) && isset($_GET['special']) && (isset($_GET['type']) || $_GET['special'] == 5)) {
    $jour = $_GET['annee'].'-'.$_GET['mois'].'-'.$_GET['jour'];

    if ($_GET['special'] != 5 && ($_GET['type'] < 0 || $_GET['type'] > 6))
      send('error', 'Mauvais jours donné');
    elseif ($_GET['special'] == 5 && isset($_GET['jours']))
      $type = 50 + $_GET['jours'];
    else {
      if ($_GET['special'] >= 0 && $_GET['special'] < 5)
        $type = intval($_GET['type']) + (10 * $_GET['special']);
      else
        send('error', 'Impossible de connaitre le type de journée');
    }

    if ($type < 40) {
      if (!isset($_GET['alternance']) && !isset($_GET['semaine']) || ($_GET['alternance'] != 'A' && $_GET['alternance'] != 'B') || (!is_numeric($_GET['semaine']) && $type < 6))
        send('error', 'Problème d\'entrée avec les semaines et/ou alternances');

      if (isset($_GET['infos'])) {
          $query = $GLOBALS['bdd']->prepare('INSERT INTO jours(jour, type, alternance, semaine, infos) VALUES(?, ?, ?, ?, ?)');
          addValue($query, array($jour, $type, $_GET['alternance'], $_GET['semaine'], $_GET['infos']));
      }
      else {
          $query = $GLOBALS['bdd']->prepare('INSERT INTO jours(jour, type, alternance, semaine) VALUES(?, ?, ?, ?)');
          addValue($query, array($jour, $type, $_GET['alternance'], $_GET['semaine']));
      }
    }
    else {
      if (isset($_GET['infos'])) {
          $query = $GLOBALS['bdd']->prepare('INSERT INTO jours(jour, type, infos) VALUES(?, ?, ?)');
          addValue($query, array($jour, $type, $_GET['infos']));
      }
      elseif ($type < 50) {
          $query = $GLOBALS['bdd']->prepare('INSERT INTO jours(jour, type) VALUES(?, ?)');
          addValue($query, array($jour, $type));
      }
      else
        send('error', 'Il est nécessaire de donner une info');
    }
  }
?>
