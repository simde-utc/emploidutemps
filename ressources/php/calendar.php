<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/include.php');
  include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/functions/tabs.php');
  include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/functions/groups.php');
  include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/functions/tasks.php');
  include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/functions/exchanges.php');

  header('Content-Type: application/json');
  $title = '';
  $tabs = array();
  $groups = array();
  $active = array();
  $tasks = array();

  function returnData($mode) {
    $data = array(
    'title' => $GLOBALS['title'],
    'tabs' => $GLOBALS['tabs'],
    'groups' => $GLOBALS['groups'],
    'tasks' => $GLOBALS['tasks'],
    'infos' => array(
      'login' => $_SESSION['login'],
      'uvs' => $_SESSION['uvs'],
      'colors' => $GLOBALS['colors'],
      'sides' => 1,
      'week' => array(),
      'get' => $_GET,
      'active' => $GLOBALS['active'],
      'nbrActive' => count($_SESSION['active']) + 1
    ));

    if ($GLOBALS['mode'] == 'organiser') {
      $data['infos']['active'] = $GLOBALS['active'];
      $data['infos']['nbrActive'] = count($_SESSION['active']) + 1;
    }

    $data['infos']['get']['mode'] = $mode;
    $data['infos']['get']['week'] = $_SESSION['week'];

    if ($GLOBALS['mode'] == 'organiser' || $GLOBALS['mode'] == 'semaine') {
      $data['infos']['daysInfo'] = $GLOBALS['daysInfo'];

      $date = new DateTime($_SESSION['week']);
      $date->modify('-7 day');
      $day = $date->format('Y-m-d');

      $data['infos']['week']['before'] = (isAGoodDate($day) ? $day : FALSE);

      $day = date('Y-m-d', strtotime('monday this week'));

      $data['infos']['week']['actual'] = ($_SESSION['week'] == $day ? FALSE : $day);

      $date->modify('+14 day');
      $day = $date->format('Y-m-d');

      $data['infos']['week']['after'] = (isAGoodDate($day) ? $day : FALSE);
    }

    if ($mode == 'comparer' || ($mode == 'modifier' && (!isset($_GET['mode_type']) || $_GET['mode_type'] != 'original')))
      $data['infos']['sides'] = 2;

    echo json_encode($data);
    exit;
  }

  /*  TRAITEMENT  */

  if (isset($_GET['mode']) && is_string($_GET['mode']) && !empty($_GET['mode']))
    $mode = $_GET['mode'];
  else
    $mode = $_SESSION['mode'];

  if ($mode == 'modifier') {
    $type = isset($_GET['mode_type']) && is_string($_GET['mode_type']) ? $_GET['mode_type'] : NULL;
    $mode_option = isset($_GET['mode_option']) && is_string($_GET['mode_option']) ? $_GET['mode_option'] : NULL;

    if ($type == 'received') {
      printUVsFollowed($_SESSION['login'], 1);
      printExchangesReceived($_SESSION['login'], $mode_option);
      $title = 'Affichage des demandes d\'échanges reçues';
    }
    elseif ($type == 'sent') {
      printUVsFollowed($_SESSION['login'], 1);
      printExchangesSent($_SESSION['login'], $mode_option);
      $title = 'Affichage des demandes d\'échanges envoyés';
    }
    elseif ($type == 'canceled') {
      printUVsFollowed($_SESSION['login'], 1);
      printExchangesCanceled($_SESSION['login'], $mode_option);
      $title = 'Affichage des échanges en demande d\'annulation';
    }
    elseif ($type == 'original') {
      printUVsFollowed($_SESSION['login'], 0, 1, 0);
      printUVsFollowed($_SESSION['login'], 0, 0, 1);
      $title = 'Affichage de ton emploi du temps original';
    }
    elseif ($type == 'changement') {
      printUVsFollowed($_SESSION['login'], 1, 1, 0);
      printUVsFollowed($_SESSION['login'], 2, 1, 1);
      printUVsFollowed($_SESSION['login'], 2, 0, 1);
      $title = 'Affichage des changements effectués sur ton emploi du temps';
    }
    else {
      printUVsFollowed($_SESSION['login'], 1);

      if (isset($_GET['uv']) && is_string($_GET['uv']) && !empty($_GET['uv'])) {
        $type = 'uvs_followed';
        $_GET['mode_type'] = 'uvs_followed';
        $UVType = (isset($_GET['type']) && is_string($_GET['type']) && !empty($_GET['type']) ? $_GET['type'] : NULL);
        $title = 'Affichage des autres disponibilités de '.uvTypeToText($UVType).' de '.$_GET['uv'];
        printUV($_GET['uv'], 2, $UVType);
      }
      else
        $title = 'Sélectionne un cours/TD/TP pour afficher ses autres disponibilités';
    }

    if ($mode_option == 'available')
      $title .= ' en attente de réponse';
    elseif ($mode_option == 'accepted')
      $title .= ' acceptée';
    elseif ($mode_option == 'refused')
      $title .= ' refusée';

    printModifierTabs($type);
  }
  elseif ($mode == 'comparer') {
    printUVsFollowed($_SESSION['login'], 1);

    if (isset($_GET['login']) && is_string($_GET['login']) && isAStudent($_GET['login']) && $_GET['login'] != $_SESSION['login']) {
      $infos = getStudentInfos($_GET['login']);
      $title = 'Comparaison entre ton emploi du temps et celui de '.($infos['surname'] != '' ? $infos['firstname'].' '.$infos['surname'] : $_GET['login']);
      addToOthers($_GET['login']);
      printUVsFollowed($_GET['login'], 2);
    }
    elseif (isset($_GET['uv']) && is_string($_GET['uv']) && !empty($_GET['uv'])) {
      $title = 'Comparaison entre ton emploi du temps et celui de l\'UV '.$_GET['uv'];
      addToOthers($_GET['uv']);
      printUV($_GET['uv'], 2);
    }
    else
      $title = 'Sélectionne un étudiant ou une UV avec qui/laquelle comparer ton emploi du temps';

    printGroupTabs();
  }
  elseif ($mode == 'organiser') {
    $days = getDays($_SESSION['week']);

    if (isset($_GET['addActive']) && is_array($_GET['addActive'])) {
      foreach ($_GET['addActive'] as $login)
        addActive($login);
    }

    if (isset($_GET['delActive']) && is_array($_GET['delActive'])) {
      foreach ($_GET['delActive'] as $login)
        delActive($login);
    }

    if (isset($_GET['setActive']) && is_array($_GET['setActive'])) {
      $_SESSION['active'] = array();
      foreach ($_GET['setActive'] as $login)
        addActive($login);
    }

    printManyTasks(array_merge(array($_SESSION['login']), $_SESSION['active']), $_SESSION['week']);

    $temp = new DateTime($_SESSION['week']);
    $week = $temp->format('d/m');
    $title = 'Affichage des différents emplois du temps sélectionnés de la semaine du '.$week;

    printWeek(NULL, $_SESSION['week'], 'calendar');
    printGroupTabsInfos();
    printAddGroupTab();
  }
  elseif ($mode == 'semaine') {
    if (isset($_GET['mode_type']) && is_string($_GET['mode_type']))
      $type = $_GET['mode_type'];
    else
      $type = NULL;

    $days = getDays($_SESSION['week']);
    $temp = new DateTime($_SESSION['week']);
    $week = $temp->format('d/m');

    if (isset($_GET['uv']) && isAnUV($_GET['uv'])) {
      $_GET['mode_type'] = 'uvs_followed';
      $type = 'uvs_followed';
      printWeek($_GET['uv'], $_SESSION['week'], 'uv');
      $title = 'Affichage des cours/TD/TP de '.$_GET['uv'].' de la semaine du '.$week;
    }
    elseif ($type == 'uvs_followed') {
      printWeek($_SESSION['login'], $_SESSION['week'], 'uv_followed');
      $title = 'Affichage de tes cours/TD/TP de la semaine du '.$week;
    }
    elseif ($type == 'events') {
      printWeek($_SESSION['login'], $_SESSION['week'], 'event');
      $title = 'Affichage de tes évènements de la semaine du '.$week;
    }
    elseif ($type == 'meetings') {
      printWeek($_SESSION['login'], $_SESSION['week'], 'meeting');
      $title = 'Affichage de tes réunions de la semaine du '.$week;
    }
    elseif ($type == 'rooms') {
      $gap = isset($_GET['mode_option']) && is_numeric($_GET['mode_option']) ? intval($_GET['mode_option']) : 1;
      printWeek($gap, $_SESSION['week'], 'room');
      $title = 'Affichage des salles disponibles de '.abs($gap).' à '.(abs($gap) + 1).'h de la semaine du '.$week;
    }
    else {
      $type = NULL;

      printWeek($_SESSION['login'], $_SESSION['week'], 'uv_followed');
      printWeek($_SESSION['login'], $_SESSION['week'], 'event');
      printWeek($_SESSION['login'], $_SESSION['week'], 'meeting');
      $title = 'Affichage de ton emploi du temps de la semaine du '.$week;
    }

    printSemaineTabs($type);
    printWeek(NULL, $_SESSION['week'], 'calendar');
  }
  else {
    $mode = 'classique';
    if (isset($_GET['mode_type']) && $_GET['mode_type'] == 'rooms') {
      $gap = isset($_GET['mode_option']) && is_numeric($_GET['mode_option']) ? intval($_GET['mode_option']) : 1;
      printRoomTasks($gap);
      $title = 'Affichage des salles disponibles de '.abs($gap).' à '.(abs($gap) + 1).'h';
      printMyTab(FALSE);
    }
    elseif (isset($_GET['uv']) && is_string($_GET['uv']) && !empty($_GET['uv'])) {
      addToOthers($_GET['uv']);
      printUV($_GET['uv'], 0);
      $title = 'Affichage de l\'emploi du temps classique de '.$_GET['uv'];
      printMyTab(FALSE);
    }
    else {
      $login = isset($_GET['login']) && is_string($_GET['login']) && !empty($_GET['login']) ? $_GET['login'] : $_SESSION['login'];
      addToOthers($login);
      printUVsFollowed($login);
      if ($login == $_SESSION['login']) {
        $title = 'Affichage de ton emploi du temps classique';
        printMyTab(TRUE);
      }
      else {
        $infos = getStudentInfos($login);
        $title = 'Affichage de l\'emploi du temps de '.($infos['surname'] == '' ? $login : $infos['firstname'].' '.$infos['surname']);
        printMyTab(FALSE);
      }
    }

    printRoomTabs(isset($_GET['mode_type']) ? $_GET['mode_type'] : NULL);
    printGroupTabs();
  }

  returnData($mode);
?>
