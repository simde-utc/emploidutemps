<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/include.php');
  if (isset($_GET['mode']) && is_string($_GET['mode']) && !empty($_GET['mode']))
    $mode = $_GET['mode'];
  else
    $mode = 'afficher';

  if (isset($_GET['login']) && is_string($_GET['login']) && !empty($_GET['login'])) {
    $info = getEtu($_GET['login']);

    if ($info['mail'] == NULL)
      $name = $_GET['login'];
    else
      $name = $info['nom'].' '.$info['prenom'];
  }

  if ($mode == 'comparer') {
    if (isset($_GET['login']) && is_string($_GET['login']) && !empty($_GET['login']) && $_GET['login'] != $_SESSION['login'])
      echo 'Comparaison entre ton emploi du temps et celui de ', $name;
    elseif (isset($_GET['uv']) && is_string($_GET['uv']) && !empty($_GET['uv']))
      echo 'Comparaison entre ton emploi du temps et celui de l\'UV ', $_GET['uv'];
    else
      echo 'Cliquer sur + permet d\'ajouter des étudiants ou une UV à comparer';
  }
  elseif ($mode == 'modifier') {
    if (isset($_GET['uv']) && is_string($_GET['uv']) && !empty($_GET['uv']) && isset($_GET['type']) && is_string($_GET['type']) && !empty($_GET['type']))
      echo 'Affichage des alternatives à ton ', ($_GET['type'] == 'D' ? $_GET['type'] = 'TD' : ($_GET['type'] == 'C' ? $_GET['type'] = 'cours' : $_GET['type'] = 'TP')), ' de ', $_GET['uv'];
    else if (isset($_GET['recu']) && is_string($_GET['recu']) && !empty($_GET['recu'])) {
      if ($_GET['recu'] == 'nouveau')
        echo 'Affichage des demandes reçues en attente';
      else if ($_GET['recu'] == 'refuse')
        echo 'Affichage des demandes reçues refusées';
      else if ($_GET['recu'] == 'accepte')
        echo 'Affichage des demandes reçues acceptées';
      else
        echo 'Affichage de toutes les demandes reçues';
    }
    else if (isset($_GET['envoi']) && is_string($_GET['envoi']) && !empty($_GET['envoi'])) {
      if ($_GET['envoi'] == 'nouveau')
        echo 'Affichage des demandes envoies en attente';
      else if ($_GET['envoi'] == 'refuse')
        echo 'Affichage des demandes envoies refusées';
      else if ($_GET['envoi'] == 'accepte')
        echo 'Affichage des demandes envoies acceptées';
      else
        echo 'Affichage de toutes les demandes envoies';
    }
    else if (isset($_GET['annule']) && is_string($_GET['annule']) && $_GET['annule'] == '1')
      echo 'Affichage des demandes d\'annulation';
    else if (isset($_GET['original']) && is_string($_GET['original']) && $_GET['original'] == '1')
      echo 'Affichage de ton emploi du temps originel';
    else if (isset($_GET['changement']) && is_string($_GET['changement']) && $_GET['changement'] == '1')
      echo 'Affichage des changements avec ton emploi du temps originel';
    else
      echo 'Cliquer sur une UV permet d\'afficher ses alternatives';
  }
  elseif ($mode == 'organiser') {
    echo 'Affichage des horaires occupés par les étudiants sélectionnés';
  }
  elseif ($mode == 'planifier') {
    if (isset($_GET['cours']))
      echo 'Affichage des cours de la semaine';
    else if (isset($_GET['event']))
      echo 'Affichage des évènements de la semaine';
    else if (isset($_GET['reu']))
      echo 'Affichage des réunions de la semaine';
    else if (isset($_GET['salle']))
      echo 'Affichage des salles disponibles durant la semaine';
    else
      echo 'Affichage de ton emploi du temps de la semaine';
  }
  else {
    if (isset($_GET['uv']) && is_string($_GET['uv']) && !empty($_GET['uv']))
      echo 'Affichage de l\'emploi du temps de l\'UV ', $_GET['uv'];
    elseif (isset($_GET['login']) && is_string($_GET['login']) && !empty($_GET['login']) && $_GET['login'] != $_SESSION['login'])
      echo 'Affichage de l\'emploi du temps de ', $name;
    else
      echo 'Affichage de ton emploi du temps';
  }
?>
