<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/include.php');

  $title = '';
  $tabs = array();
  $tasks = array();
  $id = 0;

  function returnData($mode) {
    header('Content-Type: application/json');

    echo json_encode(array(
      'title' => $GLOBALS['title'],
      'tabs' => $GLOBALS['tabs'],
      'tasks' => $GLOBALS['tasks'],
      'infos' => array(
        'login' => $_SESSION['login'],
        'week' => $_SESSION['week'],
        'mode' => $mode,
        'get' => $_GET
      )
    ));

    exit;
  }

  function addTask($taskType, $info, $infosArrays, $side = NULL) {
    if ($infosArrays == array())
      return;

    // Création de la tâche
    $tasks = array( // Foutre l'id en arguement dans infos et assembler les tasks en fonction du même type/login
      'type' => $taskType,
      'info' => $info,
    );

    if ($side != NULL)
      $tasks['side'] = $side;

    // Préparation de toutes les tâches sous le même type/login/side
    $tasks['data'] = array();

    // Ajout de chaque tâche
    foreach ($infosArrays as $infosArray) {
      // Conversion de minutes en heures
      $exploded = explode(':', $infosArray['begin']);
      $begin = floatval(join('.', array($exploded[0], 100/60*$exploded[1])));
      $exploded = explode(':', $infosArray['end']);
      $end = floatval(join('.', array($exploded[0], 100/60*$exploded[1])));

      // Définition de la tâche
      $id = $GLOBALS['id']++;
      $task = array(
        'subject' => NULL,
        'description' => NULL,
        'location' => NULL,
        'day' => intval($infosArray['day']),
        'duration' => $end - $begin,
        'startTime' => $begin,
        'timeText' => ($end - $begin == 24 ? 'Journée' : $infosArray['begin'].' - '.$infosArray['end']),
        'bgColor' => (isset($infosArray['color']) ? $infosArray['color'] : NULL),
      );

      // Ajout des infos en fonction du type de tâche
      if ($taskType == 'uv_followed' || $taskType == 'received' || $taskType == 'sent') {
        $task['subject'] = $infosArray['uv'];
        $task['location'] = $infosArray['room'];
        $task['idUV'] = intval($infosArray['idUV']);
        $task['type'] = $infosArray['type'];
        $task['groupe'] = intval($infosArray['groupe']);
        $task['frequency'] = intval($infosArray['frequency']);
        if ($infosArray['week'] != '')
          $task['week'] = $infosArray['week'];

        if ($taskType == 'received') {
          $id = 'r'.$infosArray['id'];
          $task['inExchange'] = $infosArray['inExchange'];
        }
        elseif ($taskType == 'sent') {
          $id = 's'.$infosArray['id'];
          $task['inExchange'] = $infosArray['inExchange'];
        }
      }
      elseif ($taskType == 'uv') {
        $task['subject'] = $infosArray['uv'];
        $task['location'] = $infosArray['room'];
        $task['idUV'] = intval($infosArray['id']);
        $task['type'] = $infosArray['type'];
        $task['groupe'] = intval($infosArray['groupe']);
        $task['nbrEtu'] = intval($infosArray['nbrEtu']);
        $task['frequency'] = intval($infosArray['frequency']);
        if ($infosArray['week'] != '')
          $task['week'] = $infosArray['week'];
      }
      elseif ($taskType == 'organize') {
        $task['subject'] = (isset($infosArray['subject']) ? $infosArray['subject'] : $infosArray['uv']);
        $task['location'] = $infosArray['room'];
      }
      elseif ($taskType == 'event') {
        $task['subject'] = $infosArray['subject'];
        $task['description'] = $infosArray['description'];
        $task['location'] = $infosArray['location'];
      }
      elseif ($taskType == 'room') {
        $task['subject'] = $infosArray['subject'];
        $task['description'] = $infosArray['description'];
      }
      elseif ($taskType == 'calendar') {
        $task['subject'] = $infosArray['subject'];
        $task['description'] = $infosArray['description'];
        $task['location'] = $infosArray['location'];
        $task['bgColor'] = '#FFFFFF';
      }

      $tasks['data'][$id] = $task;
    }

    array_push($GLOBALS['tasks'], $tasks);
  }

  function addGroupTabs($name, $group) {
    $GLOBALS['tabs'][$name] = array(
      'active' => FALSE,
    );
    $groupActive = 1;

    foreach ($group as $sub_name => $sub_group) {
      if (is_string($sub_group)) {
        $sub_name = 'all';
        $sub_group = array($sub_group);
      }

      foreach ($sub_group as $login) {
        $data = getEtu($login);
        $notActive = array_keys($_SESSION['etuActive'], $login) == -1;
        $groupActive += $notActive;

        $GLOBALS['tabs'][$name][$sub_name][$login] = array(
          'login' => $login,
          'surname' => $data['surname'],
          'firstname' => $data['firstname'],
          //'role' =>
          'isActive' => !$notActive
        );
      }
    }

    if ($groupActive == 1)
      $GLOBALS['tabs'][$name]['active'] = TRUE;
  }

  // Affichage de les cours d'une personne sur une semaine générale
  function printUVsFollowed($login, $side = NULL, $enabled = 1, $exchanged = NULL) {
    $tasks = getUVsFollowed($login, $enabled, $exchanged);

    foreach ($tasks as $task) {
      if ($enabled == 0 && $exchanged == 1)
        $task['color'] = '#FF0000';
      elseif ($enabled == 1 && $exchanged == 1)
        $task['color'] = '#00FF00';
      elseif ($task['color'] == NULL)
        $task['color'] = $task['colorUV'];
    }

    addTask('uv_followed', $login, $tasks, $side);
  }

  // Affichage de l'edt d'une UV sur une semaine générale
  function printUV($uv, $side = NULL, $type = NULL) {
    $tasks = getEdtUV($uv, $type);

    foreach ($tasks as $task) {
      $query = $GLOBALS['bdd']->prepare('SELECT uvs_followed.color FROM uvs, uvs_followed WHERE uvs.uv = ? AND uvs_followed.login = ? AND uvs.id = uvs_followed.idUV LIMIT 1');
      $GLOBALS['bdd']->execute($query, array($uv, $_SESSION['login']));

      if ($query->rowCount() == 1) {
        $data = $query->fetch();
        if ($data['color'] != NULL)
          $task['color'] = $data['color'];
      }
    }

    addTask('uv', $uv, $tasks, $side);
  }

  // Affichage des demandes d'échanges reçues
  function printExchangesReceived($login, $info = NULL) {
    $tasks = array();

    if ($stat == 'available') {
      $infos = getExchangesReceived($login, NULL, NULL, 1, 0); // Actives
      if ($infos != array())
        array_push($tasks, $infos);
    }
    elseif ($stat == 'exchanged') {
      $infos = getExchangesReceived($login, NULL, NULL, 0, 1); // Echangées
      if ($infos != array())
        array_push($tasks, $infos);
      $infos = getExchangesReceived($login, NULL, NULL, 1, 1); // Annulées
      if ($infos != array())
        array_push($tasks, $infos);
    }
    elseif ($stat == 'refused') {
      $infos = getExchangesReceived($login, NULL, NULL, 0, 0); // Refusées
      if ($infos != array())
        array_push($tasks, $infos);
    }
    else {
      $infos = getExchangesReceived($login, NULL, NULL, 0, 0); // Refusées
      if ($infos != array())
        array_push($tasks, $infos);
      $infos = getExchangesReceived($login, NULL, NULL, 1, 0); // Actives
      if ($infos != array())
        array_push($tasks, $infos);
      $infos = getExchangesReceived($login, NULL, NULL, 0, 1); // Echangées
      if ($infos != array())
        array_push($tasks, $infos);
      $infos = getExchangesReceived($login, NULL, NULL, 1, 1); // Annulées
      if ($infos != array())
        array_push($tasks, $infos);
    }

    foreach ($tasks as $task) {
      print_r($task);
      $infos = getUVFromIdUV($task['idUV']);
      $infos2 = getUVFromIdUV($task['idUV2']);

      array_push($task, array(
        'uv' => $infos['uv'],
        'room' => $infos['room'],
        'type' => $infos['type'],
        'groupe' => $infos['groupe'],
        'frequency' => $infos['frequency'],
        'week' => $infos['week']
      ));

      array_push($task, array('inExchange' => $infos2));
    }

    addTask('exchange_received', $login, $tasks, 2);
  }

  // Affichage des demandes d'échanges reçues
  function printExchangesSent($login, $stat = '1') {
    $tasks = array();

    if ($stat == 'available') {
      $infos = getExchangesSent($login, NULL, NULL, 1, 0); // Actives
      if ($infos != array())
        array_push($tasks, $infos);
    }
    elseif ($stat == 'exchanged') {
      $infos = getExchangesSent($login, NULL, NULL, 0, 1); // Echangées
      if ($infos != array())
        array_push($tasks, $infos);
      $infos = getExchangesSent($login, NULL, NULL, 1, 1); // Annulées
      if ($infos != array())
        array_push($tasks, $infos);
    }
    elseif ($stat == 'refused') {
      $infos = getExchangesSent($login, NULL, NULL, 0, 0); // Refusées
      if ($infos != array())
        array_push($tasks, $infos);
    }
    else {
      $infos = getExchangesSent($login, NULL, NULL, 0, 0); // Refusées
      if ($infos != array())
        array_push($tasks, $infos);
      $infos = getExchangesSent($login, NULL, NULL, 1, 0); // Actives
      if ($infos != array())
        array_push($tasks, $infos);
      $infos = getExchangesSent($login, NULL, NULL, 0, 1); // Echangées
      if ($infos != array())
        array_push($tasks, $infos);
      $infos = getExchangesSent($login, NULL, NULL, 1, 1); // Annulées
      if ($infos != array())
        array_push($tasks, $infos);
    }

    foreach ($tasks as $task) {
      print_r($task);
      $infos = getUVFromIdUV($task['idUV']);
      $infos2 = getUVFromIdUV($task['idUV2']);

      array_push($task, array(
        'uv' => $infos2['uv'],
        'room' => $infos2['room'],
        'type' => $infos2['type'],
        'groupe' => $infos2['groupe'],
        'frequency' => $infos2['frequency'],
        'week' => $infos2['week']
      ));

      array_push($task, array('inExchange' => $infos));
    }

    addTask('exchange_sent', $login, $tasks, 2);
  }

  // Affichage des demandes d'échanges reçues
  function printExchangesCanceled($login) {
    addTask('exchanges_canceled', $login, getExchangesCanceled($login), 2);
  }

  function printManyTasks($logins, $week) {
    foreach ($logins as $login)
      printWeek($login, $week, array('uv_followed', 'event', 'meeting'));

    for ($i = 0; $i < count($GLOBALS['tasks']); $i++) {
      $GLOBALS['tasks'][$i]['type'] = 'organize';
    }
  }

  // Affichage des taches demandées en fonction d'une semaine précise
  function printWeek($info, $week, $taskTypes = 'uv_followed', $nbrOfDays = 7) {
    $days = getDays($week, $nbrOfDays);

    if (is_string($taskTypes))
      $taskTypes = array($taskTypes);

    foreach($taskTypes as $taskType) {
      $tasks = array();

      foreach ($days as $i => $day) {
        if ($taskType == 'calendar')
          $tasksDay = array($day);
        elseif ($taskType == 'event')
          $tasksDay = array();
        elseif ($taskType == 'meeting')
          $tasksDay = array();
        elseif ($taskType == 'room') {
          if (!$day['C'] && !$day['D'])
            continue;
          $tasksDay = getRooms($info, $day['day']);
        }
        else
          $tasksDay = getUVsFollowed($info, 1, NULL, $day['day']);

        foreach ($tasksDay as $taskDay) {
          if ($taskType == 'calendar') {
            if ($taskDay['subject'] == NULL || $taskDay['subject'] == '')
              continue;

            $taskDay['begin'] = '00:00';
            $taskDay['end'] = '24:00';
            $taskDay['bgColor'] = '#FFFFFF';
          }
          elseif ($taskType != 'room') {
            if (!$day[$taskDay['type']] || ($day['week'] === 'A' && $taskDay['week'] === 'B') || ($day['week'] === 'B' && $taskDay['week'] === 'A'))
              continue;
          }

          $taskDay['day'] = $i;
          array_push($tasks, $taskDay);
        }
      }

      addTask($taskType, $info, $tasks);
    }
  }


  // Traitrement de la demande
  if (isset($_GET['mode']) && is_string($_GET['mode']) && !empty($_GET['mode']))
    $mode = $_GET['mode'];
  else
    $mode = 'classique';

  if ($mode == 'comparaison') {
    printUVsFollowed($_SESSION['login'], 1);

    if (isset($_GET['login']) && is_string($_GET['login']) && !empty($_GET['login']) && $_GET['login'] != $_SESSION['login'])
      printUVsFollowed($_GET['login'], 2);
    elseif (isset($_GET['uv']) && is_string($_GET['uv']) && !empty($_GET['uv']))
      printUV($_GET['uv'], 2);
  }
  elseif ($mode == 'modification') {
    if (isset($_GET['mode_type']) && is_string($_GET['mode_type']))
      $type = $_GET['mode_type'];
    else
      $type = '';

    if ($type == 'original') {
      printUVsFollowed($_SESSION['login'], 0, 1, 0);
      printUVsFollowed($_SESSION['login'], 0, 0, 1);
    }
    elseif ($type == 'changement') {
      printUVsFollowed($_SESSION['login'], 1, 1);
      printUVsFollowed($_SESSION['login'], 2, 0, 1);
      printUVsFollowed($_SESSION['login'], 2, 1, 1);
    }
    elseif ($type == 'received') {
      printUVsFollowed($_SESSION['login'], 1);
      printExchangesReceived($_SESSION['login'], isset($_GET['info']) && is_string($_GET['info']) ? $_GET['info'] : NULL);
    }
    elseif ($type == 'sent') {
      printUVsFollowed($_SESSION['login'], 1);
      printExchangesSent($_SESSION['login'], isset($_GET['info']) && is_string($_GET['info']) ? $_GET['info'] : NULL);
    }
    elseif ($type == 'canceled') {
      printUVsFollowed($_SESSION['login'], 1);
      printExchangesCanceled($_SESSION['login']);
    }
    else {
      printUVsFollowed($_SESSION['login'], 1);

      if (isset($_GET['uv']) && is_string($_GET['uv']) && !empty($_GET['uv']))
        printUV($_GET['uv'], 2, (isset($_GET['type']) && is_string($_GET['type']) && !empty($_GET['type']) ? $_GET['type'] : NULL));
    }
  }
  elseif ($mode == 'semaine') {
    if (isset($_GET['mode_type']) && is_string($_GET['mode_type']))
      $type = $_GET['mode_type'];
    else
      $type = '';

    if ($type == 'uvs_followed')
      printWeek($_SESSION['login'], $_SESSION['week'], 'uv_followed');
    elseif ($type == 'events')
      printWeek($_SESSION['login'], $_SESSION['week'], 'event');
    elseif ($type == 'meetings')
      printWeek($_SESSION['login'], $_SESSION['week'], 'meeting');
    elseif ($type == 'organize') {
      $_SESSION['activeEtus'] = array('jderrien');
      printManyTasks(array_merge(array($_SESSION['login']), $_SESSION['activeEtus']), $_SESSION['week']);
      addGroupTabs('logins', $_SESSION['activeEtus']);
    }
    elseif ($type == 'rooms')
      printWeek(isset($_GET['gap']) && is_numeric($_GET['gap']) ? intval($_GET['gap']) : 2, $_SESSION['week'], 'room');
    else {
      printWeek($_SESSION['login'], $_SESSION['week'], 'uv_followed');
      printWeek($_SESSION['login'], $_SESSION['week'], 'event');
      printWeek($_SESSION['login'], $_SESSION['week'], 'meeting');
    }

    printWeek(NULL, $_SESSION['week'], 'calendar');
  }
  else {
    $mode = 'classique';
    if (isset($_GET['uv']) && is_string($_GET['uv']) && !empty($_GET['uv']))
      printUV($_GET['uv'], 0, (isset($_GET['type']) && is_string($_GET['type']) && !empty($_GET['type']) ? $_GET['type'] : NULL));
    else
      printUVsFollowed(isset($_GET['login']) && is_string($_GET['login']) && !empty($_GET['login']) ? $_GET['login'] : $_SESSION['login']);
  }

  returnData($mode);

?>
