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
      echo 'Comparaison entre votre emploi du temps et celui de ', $name;
    elseif (isset($_GET['uv']) && is_string($_GET['uv']) && !empty($_GET['uv']))
      echo 'Comparaison entre votre emploi du temps et celui de l\'UV ', $_GET['uv'];
    else
      echo 'Cliquer sur + permet d\'ajouter des étudiants ou une UV à comparer';
  }
  elseif ($mode == 'modifier') {
    if (isset($_GET['uv']) && is_string($_GET['uv']) && !empty($_GET['type']) && isset($_GET['type']) && is_string($_GET['type']) && !empty($_GET['type']))
      echo 'Affichage des alternatives à votre ', ($_GET['type'] == 'D' ? $_GET['type'] = 'TD' : ($_GET['type'] == 'C' ? $_GET['type'] = 'cours' : $_GET['type'] = 'TP')), ' de ', $_GET['uv'];
    else
      echo 'Cliquer sur une UV permet d\'afficher ses alternatives';
  }
  elseif ($mode == 'organiser') {
    echo 'Affichage des horaires occupés par les étudiants colorés';
  }
  else {
    if (isset($_GET['uv']) && is_string($_GET['uv']) && !empty($_GET['uv']))
      echo 'Affichage de l\'emploi du temps de l\'UV ', $_GET['uv'];
    elseif (isset($_GET['login']) && is_string($_GET['login']) && !empty($_GET['login']) && $_GET['login'] != $_SESSION['login'])
      echo 'Affichage de l\'emploi du temps de ', $name;
    else
      echo 'Affichage de votre emploi du temps';
  }

  if (count($_SESSION['tab']['etu']) == 0 && count($_SESSION['tab']['uv']) == 0)
    echo '<script>window.tab = 0;</script>';
  elseif (isset($_GET['tab']) && is_string($_GET['tab']) && $_GET['tab'] == 1)
    echo ' - Mode suppression'
?>
