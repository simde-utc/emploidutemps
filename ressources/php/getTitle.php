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
    if (isset($_GET['uv']) && is_string($_GET['uv']) && !empty($_GET['type']) && isset($_GET['type']) && is_string($_GET['type']) && !empty($_GET['type']))
      echo 'Affichage des alternatives à ton ', ($_GET['type'] == 'D' ? $_GET['type'] = 'TD' : ($_GET['type'] == 'C' ? $_GET['type'] = 'cours' : $_GET['type'] = 'TP')), ' de ', $_GET['uv'];
    else
      echo 'Cliquer sur une UV permet d\'afficher ses alternatives';
  }
  elseif ($mode == 'organiser') {
    echo 'Affichage des horaires occupés par les étudiants sélectionnés';
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
