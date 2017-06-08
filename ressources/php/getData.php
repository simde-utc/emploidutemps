<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/include.php');

  header('Content-Type: application/json');
  $title = '';
  $tabs = array();
  $tasks = array();
  $id = 0;

  function returnData($mode) {
    $data = array(
    'title' => $GLOBALS['title'],
    'tabs' => $GLOBALS['tabs'],
    'tasks' => $GLOBALS['tasks'],
    'infos' => array(
      'login' => $_SESSION['login'],
      'uvs' => $_SESSION['uvs'],
      'colors' => $GLOBALS['colors'],
      'sides' => 1,
      'get' => $_GET
    ));

    $data['infos']['get']['mode'] = $mode;
    $data['infos']['get']['week'] =  $_SESSION['week'];

    if ($mode == 'comparer' || ($mode == 'modifier' && (!isset($_GET['mode_type']) || $_GET['mode_type'] != 'original')))
      $data['infos']['sides'] = 2;

    echo json_encode($data);
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
        'note' => NULL,
        'description' => NULL,
        'location' => NULL,
        'day' => intval($infosArray['day']),
        'duration' => $end - $begin,
        'startTime' => $begin,
        'timeText' => ($end - $begin == 24 ? 'Journée' : $infosArray['begin'].'-'.$infosArray['end']),
        'bgColor' => (isset($infosArray['color']) ? $infosArray['color'] : getARandomColor()),
      );

      // Ajout des infos en fonction du type de tâche
      if ($taskType == 'uv_followed' || $taskType == 'received' || $taskType == 'sent') {
        $task['subject'] = $infosArray['uv'];
        $task['location'] = $infosArray['room'];
        $task['idUV'] = intval($infosArray['idUV']);
        $task['type'] = $infosArray['type'];
        $task['groupe'] = intval($infosArray['groupe']);
        $task['frequency'] = intval($infosArray['frequency']);
        $task['nbrEtu'] = intval($infosArray['nbrEtu']);
        if ($infosArray['week'] != '') {
          $task['note'] = 'Sem. '.$infosArray['week'];
          $task['week'] = $infosArray['week'];
        }

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
        $task['note'] = intval($infosArray['nbrEtu']).' étudiants';
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
        $task['description'] = $infosArray['location'];
        if ($task['timeText'] != 'Journée')
          $task['timeText'] = $infosArray['description'];
      }
      elseif ($taskType == 'calendar') {
        $task['subject'] = $infosArray['subject'];
        $task['description'] = $infosArray['description'];
        $task['location'] = $infosArray['location'];
        $task['bgColor'] = '#FFFFFF';
      }

      $task['id'] = $id;
      array_push($tasks['data'], $task);
    }

    array_push($GLOBALS['tasks'], $tasks);
  }

  // classique de les cours d'une personne sur une semaine générale
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

  // classique de l'edt d'une UV sur une semaine générale
  function printUV($uv, $side = NULL, $type = NULL) {
    $tasks = getUV($uv, $type);

    $query = $GLOBALS['bdd']->prepare('SELECT uvs_followed.color FROM uvs, uvs_followed WHERE uvs.uv = ? AND uvs_followed.login = ? AND uvs.id = uvs_followed.idUV AND (? IS NULL OR uvs.type = ?) LIMIT 1');
    $GLOBALS['bdd']->execute($query, array($uv, $_SESSION['login'], $type, $type));

    if ($query->rowCount() == 1) {
      $data = $query->fetch();
      if ($data['color'] != NULL)
        for ($i = 0; $i < count($tasks); $i++) {
          $tasks[$i]['color'] = $data['color'];
        }
    }

    addTask('uv', $uv, $tasks, $side);
  }

  // classique des demandes d'échanges reçues
  function printExchangesReceived($login, $option = NULL) {
    $tasks = array();

    if ($option == 'available') {
      $infos = getExchangesReceived($login, NULL, NULL, 1, 0); // Actives
      if ($infos != array())
        array_push($tasks, $infos);
    }
    elseif ($option == 'exchanged') {
      $infos = getExchangesReceived($login, NULL, NULL, 0, 1); // Echangées
      if ($infos != array())
        array_push($tasks, $infos);
      $infos = getExchangesReceived($login, NULL, NULL, 1, 1); // Annulées
      if ($infos != array())
        array_push($tasks, $infos);
    }
    elseif ($option == 'refused') {
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
      $infos = getUVInfosFromIdUV($task['idUV']);
      $infos2 = getUVInfosFromIdUV($task['idUV2']);

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

  // classique des demandes d'échanges reçues
  function printExchangesSent($login, $option = NULL) {
    $tasks = array();

    if ($option == 'available') {
      $infos = getExchangesSent($login, NULL, NULL, 1, 0); // Actives
      if ($infos != array())
        array_push($tasks, $infos);
    }
    elseif ($option == 'exchanged') {
      $infos = getExchangesSent($login, NULL, NULL, 0, 1); // Echangées
      if ($infos != array())
        array_push($tasks, $infos);
      $infos = getExchangesSent($login, NULL, NULL, 1, 1); // Annulées
      if ($infos != array())
        array_push($tasks, $infos);
    }
    elseif ($option == 'refused') {
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
      $infos = getUVInfosFromIdUV($task['idUV']);
      $infos2 = getUVInfosFromIdUV($task['idUV2']);

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

  // classique des demandes d'échanges reçues
  function printExchangesCanceled($login) {
    addTask('exchange_canceled', $login, getExchangesCanceled($login), 2);
  }

  function printManyTasks($logins, $week) {
    foreach ($logins as $login) {
      if (isAStudent($login))
        printWeek($login, $week, array('uv_followed', 'event', 'meeting'));
      else
        printWeek($login, $week, array('uv'));
    }

    for ($i = 0; $i < count($GLOBALS['tasks']); $i++) {
      $GLOBALS['tasks'][$i]['type'] = 'organize';
      for ($j = 0; $j < count($GLOBALS['tasks'][$i]['data']); $j++)
        $GLOBALS['tasks'][$i]['data'][$j]['bgColor'] = $GLOBALS['colors'][$i % count($GLOBALS['colors'])];
    }
  }

  // classique des taches demandées en fonction d'une semaine précise
  function printWeek($info, $week, $taskTypes = 'uv_followed') {
    $days = $GLOBALS['days'];

    if (is_string($taskTypes))
      $taskTypes = array($taskTypes);

    foreach($taskTypes as $taskType) {
      $tasks = array();

      if ($taskType == 'calendar')
        $tasksDay = $days;
      elseif ($taskType == 'event')
        $tasksDay = array();
      elseif ($taskType == 'meeting')
        $tasksDay = array();
      elseif ($taskType == 'room')
        $tasksDay = getRooms($info);
      elseif ($taskType == 'uv')
        $tasksDay = getUV($info);
      else
        $tasksDay = getUVsFollowed($info, 1);

      foreach ($days as $i => $day) {
        foreach ($tasksDay as $j => $taskDay) {
          if ($taskType == 'calendar') {
            if ($taskDay['subject'] == NULL || $taskDay['subject'] == '' || $i != $j)
              continue;

            $taskDay['begin'] = '00:00';
            $taskDay['end'] = '24:00';
            $taskDay['bgColor'] = '#000000';
          }
          elseif ($taskDay['day'] != $day['day'])
            continue;
          elseif ($taskType == 'room') {
            if (!$day['C'] && !$day['D'])
              continue;
          }
          else {
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


  /* TABS */

  function addActiveTab($login) {
    $in = FALSE;

    foreach ($_SESSION['tabs'] as $group) {
      foreach ($group as $name => $sub_group) {
        if (is_string($sub_group)) {
          if ($name == $login)
            $in = TRUE;
        }
        else {
          foreach ($sub_group as $name => $infos) {
            if ($name == $login)
              $in = TRUE;
          }
        }
      }
    }

    if (!$in) {
      if (isAStudent($login))
        array_push($_SESSION['tabs']['others']['students'], $login);
      else if (isAnUV($login))
        array_push($_SESSION['tabs']['others']['uvs'], $login);
      else
        return FALSE;
    }

    if (array_keys($_SESSION['activeTabs'], $login) == array())
      array_push($_SESSION['activeTabs'], $login);
    return TRUE;
  }

  function delActiveTab($login) {
    $where = array_keys($_SESSION['activeTabs'], $login);

    if ($where != array()) {
      unset($_SESSION['activeTabs'][$where[0]]);
      return TRUE;
    }

    return FALSE;
  }

  function printGroupTabInfos($name, $group) {
    $groupActive = 1;
    $GLOBALS['tabs'][$name] = array(
      'type' => NULL,
      'active' => FALSE,
      'nbr' => 0
    );

    foreach ($group as $sub_name => $sub_group) {
      if (is_string($sub_group)) {
        $GLOBALS['tabs'][$name][$sub_name] = $sub_group;
        continue;
      }

      $GLOBALS['tabs'][$name][$sub_name] = array(
        'isActive' => ($sub_group == array() ? FALSE : TRUE)
      );

      foreach ($sub_group as $key => $login) {
        $GLOBALS['tabs'][$name]['nbr']++;

        if (!is_numeric($key)) {
          $role = $login;
          $login = $key;
        }
        else
          $role = NULL;

        if (isAStudent($login)) {
          $data = getStudentInfos($login);
          $extern = FALSE;
        }
        elseif (isAnUV($login)) {
          array_push($GLOBALS['tabs'][$name][$sub_name], $login);
          continue;
        }
        else {
          $data = array(
            'surname' => '(en stage)',
            'firstname' => $login
          );
          $extern = TRUE;
        }

        $notActive = array_keys($_SESSION['activeTabs'], $login) == array();
        $groupActive += $notActive;

        if ($notActive)
          $GLOBALS['tabs'][$name][$sub_name]['isActive'] = FALSE;

        $GLOBALS['tabs'][$name][$sub_name][$login] = array(
          'surname' => $data['surname'],
          'firstname' => $data['firstname'],
          'isActive' => !$notActive,
          'isExtern' => $extern
        );

        if ($role != NULL)
          $GLOBALS['tabs'][$name][$sub_name][$login]['role'] = $role;
      }
    }

    if ($groupActive == 1 && $GLOBALS['tabs'][$name]['nbr'] != 0)
      $GLOBALS['tabs'][$name]['active'] = TRUE;
  }

  function printSeparateTab() {
    $GLOBALS['tabs'][array_keys($GLOBALS['tabs'], end($GLOBALS['tabs']))[0]]['separate'] = TRUE;
  }

  function printMyTab($selected = TRUE) {
    $GLOBALS['tabs']['me'] = array(
      'type' => 'button',
      'text' => ($_SESSION['surname'] == '' ? $_SESSION['login'] : $_SESSION['surname'].' '.$_SESSION['firstname']),
      'get' => array(
        'mode' => $GLOBALS['mode'],
        'mode_type' => NULL
      )
    );

    if ($selected)
      $GLOBALS['tabs']['me']['active'] = TRUE;
  }

  function printGroupTabs($printType = 'button') {
    if ($printType == 'button') {
      printMyTab(!((isset($_GET['login']) && $_GET['login'] != $_SESSION['login']) || (isset($_GET['uv']) && is_string($_GET['uv']) && isAnUV($_GET['uv']))));

      foreach($_SESSION['tabs'] as $name => $group) {
        $GLOBALS['tabs'][$name] = array(
          'type' => 'button',
          'text' => $group['name'],
          'action' => 'paramTab(\''.$name.'\', \''.$printType.'\')'
        );
      }
    }
    else {
      foreach($_SESSION['tabs'] as $name => $group) {
        $groupActive = 1;
        $GLOBALS['tabs'][$name] = array(
          'type' => 'select',
          'text' => $group['name'],
          'get' => array(
            'mode' => $GLOBALS['mode'],
            'mode_type' => 'organize'
          ),
          'options' => array()
        );

        $GLOBALS['tabs'][$name]['options']['all'] = array(
          'text' => 'Tout le monde',
          'get' => array()
        );

        $all = array();

        foreach ($group as $sub_name => $sub_group) {
          $sub_groupActive = 1;

          if (is_string($sub_group) || $sub_group == array())
            continue;

          $GLOBALS['tabs'][$name]['options'][$sub_name] = array(
            'text' => $sub_name,
            'get' => array()
          );

          $get = array();

          foreach ($sub_group as $login => $role) {
            if (is_numeric($login))
              $login = $role;

            array_push($all, $login);
            array_push($get, $login);

            $notActive = array_keys($_SESSION['activeTabs'], $login) == array();
            $groupActive += $notActive;
            $sub_groupActive += $notActive;
          }

          if ($sub_groupActive == 1 && count($get) != 0) {
            $GLOBALS['tabs'][$name]['options'][$sub_name]['get']['delActiveTabs'] = $get;
            $GLOBALS['tabs'][$name]['options'][$sub_name]['active'] = TRUE;

            if (isset($_GET['mode_type']) && $_GET['mode_type'] != '')
              $GLOBALS['tabs'][$name]['active'] = TRUE;
          }
          else {
            $GLOBALS['tabs'][$name]['options'][$sub_name]['get']['addActiveTabs'] = $get;
          }
        }

        if ($groupActive == 1 && count($all) != 0) {
          $GLOBALS['tabs'][$name]['options']['all']['get']['delActiveTabs'] = $all;
          $GLOBALS['tabs'][$name]['options']['all']['active'] = TRUE;

          if (isset($_GET['mode_type']) && $_GET['mode_type'] != '')
            $GLOBALS['tabs'][$name]['active'] = TRUE;
        }
        elseif (count($all) == 0)
          unset($GLOBALS['tabs'][$name]['options']['all']);
        else {
          $GLOBALS['tabs'][$name]['options']['all']['get']['addActiveTabs'] = $all;
        }

        $GLOBALS['tabs'][$name]['options']['custom'] = array(
          'text' => 'Paramétrer..',
          'action' => 'paramTab(\''.$name.'\', \''.$printType.'\')'
        );
      }
    }

    // On déplace others à la fin (logique)
    $others = $GLOBALS['tabs']['others'];
    unset($GLOBALS['tabs']['others']);
    $GLOBALS['tabs']['others'] = $others;
  }

  function printDaysTab() {
    $date = new DateTime($_SESSION['week']);
    $date->modify('-7 day');
    $day = $date->format('Y-m-d');

    $GLOBALS['tabs']['days_before'] = array(
      'type' => 'button',
      'text' => '<i class="fa fa-arrow-left" aria-hidden="true"></i>',
      'get' => array(
        'week' => $day,
      )
    );

    if (!isAGoodDate($day))
      $GLOBALS['tabs']['days_before']['disabled'] = TRUE;

    $date->modify('+7 day');
    $day = $date->format('Y-m-d');

    $GLOBALS['tabs']['days'] = array(
      'type' => 'button',
      'text' => 'Sem. '.$date->format('d/m'),
      'get' => array(
        'week' => date('Y-m-d', strtotime('monday this week')),
      )
    );

    if (!isAGoodDate($day))
      $GLOBALS['tabs']['days']['disabled'] = TRUE;

    $date->modify('+7 day');
    $day = $date->format('Y-m-d');

    $GLOBALS['tabs']['days_after'] = array(
      'type' => 'button',
      'text' => '<i class="fa fa-arrow-right" aria-hidden="true"></i>',
      'get' => array(
        'week' => $day,
        'addActiveTabs' => NULL,
        'setActiveTabs' => NULL,
        'delActiveTabs' => NULL,
      )
    );

    if (!isAGoodDate($day))
      $GLOBALS['tabs']['days_after']['disabled'] = TRUE;
  }

  function printModiferTabs($type) {
    printMyTab($type == NULL);

    $GLOBALS['tabs']['received'] = array(
      'type' => 'select',
      'text' => 'Echange(s) reçu(s)',
      'get' => array(
        'mode' => 'modifier',
        'mode_type' => 'received'
      ),
      'options' => array(
        'all' => array(
          'text' => 'x reçu(s) au total',
          'color' => '#FFFF00'
        ),
        'available' => array(
          'text' => 'x reçu(s) en attente',
          'get' => array(
            'option' => 'available'
          ),
          'color' => '#0000FF'
        ),
        'accepted' => array(
          'text' => 'x reçu(s) accepté(s)',
          'get' => array(
            'option' => 'accepted'
          ),
          'color' => '#00FF00'
        ),
        'refused' => array(
          'text' => 'x reçu(s) refusé(s)',
          'get' => array(
            'option' => 'refused'
          ),
          'color' => '#FF0000'
        ),
      )
    );

    if ($type == 'received') {
      $get_option = (isset($_GET['mode_option']) && is_string($_GET['mode_option']) ? $_GET['mode_option'] : 'all');
      $GLOBALS['tabs']['received']['active'] = TRUE;
      $active = FALSE;

      foreach ($GLOBALS['tabs']['received']['options'] as $key => $option) {
        if (isset($option['get']['option']) && $option['get']['option'] == $get_option) {
          $GLOBALS['tabs']['received']['options'][$key]['active'] = TRUE;
          $GLOBALS['tabs']['received']['color'] = $GLOBALS['tabs']['received']['options'][$key]['color'];
          $active = TRUE;
        }
      }

      if (!$active) {
        $GLOBALS['tabs']['received']['options']['all']['active'] = TRUE;
        $GLOBALS['tabs']['received']['color'] = $GLOBALS['tabs']['received']['options'][$key]['color'];
      }
    }

    $GLOBALS['tabs']['sent'] = array(
      'type' => 'select',
      'text' => 'Echange(s) envoyé(s)',
      'get' => array(
        'mode' => 'modifier',
        'mode_type' => 'sent'
      ),
      'options' => array(
        'all' => array(
          'text' => 'x envoyé(s) au total',
          'color' => '#FFFF00'
        ),
        'available' => array(
          'text' => 'x envoyé(s) en attente',
          'get' => array(
            'option' => 'available'
          ),
          'color' => '#0000FF'
        ),
        'accepted' => array(
          'text' => 'x envoyé(s) accepté(s)',
          'get' => array(
            'option' => 'accepted'
          ),
          'color' => '#00FF00'
        ),
        'refused' => array(
          'text' => 'x envoyé(s) refusé(s)',
          'get' => array(
            'option' => 'refused'
          ),
          'color' => '#FF0000'
        ),
      )
    );

    if ($type == 'sent') {
      $get_option = (isset($_GET['mode_option']) && is_string($_GET['mode_option']) ? $_GET['mode_option'] : 'all');
      $GLOBALS['tabs']['sent']['active'] = TRUE;
      $active = FALSE;

      foreach ($GLOBALS['tabs']['sent']['options'] as $key => $option) {
        if (isset($option['get']['option']) && $option['get']['option'] == $get_option) {
          $GLOBALS['tabs']['sent']['options'][$key]['active'] = TRUE;
          $GLOBALS['tabs']['sent']['color'] = $GLOBALS['tabs']['sent']['options'][$key]['color'];
          $active = TRUE;
        }
      }

      if (!$active) {
        $GLOBALS['tabs']['sent']['options']['all']['active'] = TRUE;
        $GLOBALS['tabs']['sent']['color'] = $GLOBALS['tabs']['sent']['options'][$key]['color'];
      }
    }

    $GLOBALS['tabs']['canceled'] = array(
      'type' => 'button',
      'text' => 'Echange(s) en cours d\'annulation',
      'get' => array(
        'mode' => 'modifier',
        'mode_type' => 'canceled'
      )
    );

    if ($type == 'canceled')
      $GLOBALS['tabs']['canceled']['active'] = TRUE;

    printSeparateTab();

    $GLOBALS['tabs']['original'] = array(
      'type' => 'button',
      'text' => 'Edt original',
      'get' => array(
        'mode' => 'modifier',
        'mode_type' => 'original'
      )
    );

    if ($type == 'original')
      $GLOBALS['tabs']['original']['active'] = TRUE;

    $GLOBALS['tabs']['changement'] = array(
      'type' => 'button',
      'text' => 'Changements',
      'get' => array(
        'mode' => 'modifier',
        'mode_type' => 'changement'
      )
    );

    if ($type == 'changement')
      $GLOBALS['tabs']['changement']['active'] = TRUE;
  }

  function printSemaineTabs($type) {
    printDaysTab();
    printMyTab($type == NULL);
    printSeparateTab();

    $GLOBALS['tabs']['uvs_followed'] = array(
      'type' => 'button',
      'text' => 'Cours',
      'get' => array(
        'mode' => 'semaine',
        'mode_type' => 'uvs_followed'
      )
    );

    if ($type == 'uvs_followed')
      $GLOBALS['tabs']['uvs_followed']['active'] = TRUE;

    $GLOBALS['tabs']['events'] = array(
      'type' => 'button',
      'text' => 'Evènements',
      'get' => array(
        'mode' => 'semaine',
        'mode_type' => 'events'
      )
    );

    if ($type == 'events')
      $GLOBALS['tabs']['events']['active'] = TRUE;

    $GLOBALS['tabs']['meetings'] = array(
      'type' => 'button',
      'text' => 'Réunions',
      'get' => array(
        'mode' => 'semaine',
        'mode_type' => 'meetings'
      )
    );

    if ($type == 'meetings')
      $GLOBALS['tabs']['meetings']['active'] = TRUE;

    $gap = isset($_GET['mode_option']) && is_numeric($_GET['mode_option']) ? ' '.intval($_GET['mode_option']).'h' : '';
    $GLOBALS['tabs']['rooms'] = array(
      'type' => 'select',
      'text' => 'Salles libres'.($gap == '' ? '' : ' '.abs($gap).'-'.(abs($gap) + 1).'h'),
      'get' => array(
        'mode' => 'semaine',
        'mode_type' => 'rooms'
      ),
      'options' => array(
        '1-2' => array(
          'text' => 'De 1 à 2h',
          'get' => array(
            'mode_option' => 1
          )
        ),
        '3-4' => array(
          'text' => 'De 3 à 4h',
          'get' => array(
            'mode_option' => 3
          )
        ),
        '5-6' => array(
          'text' => 'De 5 à 6h',
          'get' => array(
            'mode_option' => 5
          )
        ),
        '7-8' => array(
          'text' => 'De 7 à 8h',
          'get' => array(
            'mode_option' => 7
          )
        ),
        '+8' => array(
          'text' => 'Pour plus de 8h',
          'get' => array(
            'mode_option' => -8
          )
        ),
      )
    );

    if ($type == 'rooms') {
      $GLOBALS['tabs']['rooms']['active'] = TRUE;
      $active = FALSE;

      foreach ($GLOBALS['tabs']['rooms']['options'] as $key => $option) {
        if ($option['get']['mode_option'] == $gap) {
          $GLOBALS['tabs']['rooms']['options'][$key]['active'] = TRUE;
          $active = TRUE;
        }
      }

      if (!$active)
        $GLOBALS['tabs']['rooms']['options']['1-2']['active'] = TRUE;
    }

    printSeparateTab();

    $GLOBALS['tabs']['organize'] = array(
      'type' => 'button',
      'text' => 'Organiser',
      'get' => array(
        'mode' => 'semaine',
        'mode_type' => 'organize'
      )
    );

    if ($type == 'organize')
      $GLOBALS['tabs']['organize']['active'] = TRUE;

    $gap = isset($_GET['mode_option']) && is_numeric($_GET['mode_option']) ? ' '.intval($_GET['mode_option']).'h' : '';

    printGroupTabs('select');
  }


  /*  TRAITEMENT  */

  if (isset($_GET['mode']) && is_string($_GET['mode']) && !empty($_GET['mode']))
    $mode = $_GET['mode'];
  else
    $mode = 'classique';

  if ($mode == 'comparer') {
    printGroupTabs();
    printUVsFollowed($_SESSION['login'], 1);

    if (isset($_GET['login']) && is_string($_GET['login']) && isAStudent($_GET['login']) && $_GET['login'] != $_SESSION['login']) {
      $infos = getStudentInfos($_GET['login']);
      $title = 'comparer entre ton emploi du temps et celui de '.($infos['surname'] != '' ? $infos['surname'].' '.$infos['firstname'] : $_GET['login']);
      printUVsFollowed($_GET['login'], 2);
    }
    elseif (isset($_GET['uv']) && is_string($_GET['uv']) && !empty($_GET['uv'])) {
      $title = 'comparer entre ton emploi du temps et celui de l\'UV '.$_GET['uv'];
      printUV($_GET['uv'], 2);
    }
    else
      $title = 'Sélectionne un étudiant ou une UV avec qui/laquelle comparer ton emploi du temps';
  }
  elseif ($mode == 'modifier') {
    if (isset($_GET['mode_type']) && is_string($_GET['mode_type']))
      $type = $_GET['mode_type'];
    else
      $type = NULL;

    if ($type == 'received') {
      printUVsFollowed($_SESSION['login'], 1);
      printExchangesReceived($_SESSION['login'], isset($_GET['mode_option']) && is_string($_GET['mode_option']) ? $_GET['mode_option'] : NULL);
      $title = 'Affichage des demandes d\'échanges reçues';
    }
    elseif ($type == 'sent') {
      printUVsFollowed($_SESSION['login'], 1);
      printExchangesSent($_SESSION['login'], isset($_GET['mode_option']) && is_string($_GET['mode_option']) ? $_GET['mode_option'] : NULL);
      $title = 'Affichage des demandes d\'échanges envoyés';
    }
    elseif ($type == 'canceled') {
      printUVsFollowed($_SESSION['login'], 1);
      printExchangesCanceled($_SESSION['login']);
      $title = 'Affichage des échanges en demande d\'annulation';
    }
    elseif ($type == 'original') {
      printUVsFollowed($_SESSION['login'], 0, 1, 0);
      printUVsFollowed($_SESSION['login'], 0, 0, 1);
      $title = 'Affichage de ton emploi du temps original';
    }
    elseif ($type == 'changement') {
      printUVsFollowed($_SESSION['login'], 1, 1);
      printUVsFollowed($_SESSION['login'], 2, 0, 1);
      printUVsFollowed($_SESSION['login'], 2, 1, 1);
      $title = 'Affichage des changements effectués sur ton emploi du temps';
    }
    else {
      $type = NULL;
      printUVsFollowed($_SESSION['login'], 1);

      if (isset($_GET['uv']) && is_string($_GET['uv']) && !empty($_GET['uv']))
        printUV($_GET['uv'], 2, (isset($_GET['type']) && is_string($_GET['type']) && !empty($_GET['type']) ? $_GET['type'] : NULL));

      $title = 'Sélectionne un cours/TD/TP pour afficher toutes les autres disponibilités';
    }

    printModiferTabs($type);
  }
  elseif ($mode == 'semaine') {
    if (isset($_GET['mode_type']) && is_string($_GET['mode_type']))
      $type = $_GET['mode_type'];
    else
      $type = NULL;

    $days = getDays($_SESSION['week']);

    if ($type == 'uvs_followed') {
      printWeek($_SESSION['login'], $_SESSION['week'], 'uv_followed');
      $title = 'Affichage des cours/TD/TP de la semaine';
    }
    elseif ($type == 'events') {
      printWeek($_SESSION['login'], $_SESSION['week'], 'event');
      $title = 'Affichage des évènements de la semaine';
    }
    elseif ($type == 'meetings') {
      printWeek($_SESSION['login'], $_SESSION['week'], 'meeting');
      $title = 'Affichage des réunions de la semaine';
    }
    elseif ($type == 'rooms') {
      $gap = isset($_GET['mode_option']) && is_numeric($_GET['mode_option']) ? intval($_GET['mode_option']) : 1;
      printWeek($gap, $_SESSION['week'], 'room');
      $title = 'Affichage des salles disponibles de '.abs($gap).' à '.(abs($gap) + 1).'h cette semaine';
    }
    elseif ($type == 'organize') {
      if (isset($_GET['addActiveTabs']) && is_array($_GET['addActiveTabs'])) {
        foreach ($_GET['addActiveTabs'] as $login)
          addActiveTab($login);
      }

      if (isset($_GET['setActiveTabs']) && is_array($_GET['setActiveTabs'])) {
        $_SESSION['activeTabs'] = array();
        foreach ($_GET['setActiveTabs'] as $login)
          addActiveTab($login);
      }

      if (isset($_GET['delActiveTabs']) && is_array($_GET['delActiveTabs'])) {
        foreach ($_GET['delActiveTabs'] as $login)
          delActiveTab($login);
      }

      printManyTasks(array_merge(array($_SESSION['login']), $_SESSION['activeTabs']), $_SESSION['week']);
      $title = 'Affichage des différents emplois du temps sélectionnés';
    }
    else {
      $type = NULL;

      printWeek($_SESSION['login'], $_SESSION['week'], 'uv_followed');
      printWeek($_SESSION['login'], $_SESSION['week'], 'event');
      printWeek($_SESSION['login'], $_SESSION['week'], 'meeting');
      $title = 'Affichage de ton emploi du temps de la semaine';
    }

    printSemaineTabs($type);
    printWeek(NULL, $_SESSION['week'], 'calendar');
  }
  else {
    $mode = 'classique';
    if (isset($_GET['uv']) && is_string($_GET['uv']) && !empty($_GET['uv'])) {
      printUV($_GET['uv'], 0, (isset($_GET['type']) && is_string($_GET['type']) && !empty($_GET['type']) ? $_GET['type'] : NULL));
      $title = 'Affichage de l\'emploi du temps de '.$_GET['uv'];
    }
    else {
      $login = isset($_GET['login']) && is_string($_GET['login']) && !empty($_GET['login']) ? $_GET['login'] : $_SESSION['login'];
      printUVsFollowed($login);
      if ($login == $_SESSION['login'])
        $title = 'Affichage de ton emploi du temps';
      else {
        $infos = getStudentInfos($login);
        $title = 'Affichage de l\'emploi du temps de '.($infos['surname'] == '' ? $login : $infos['surname'].' '.$infos['firstname']);
      }
    }

    printGroupTabs();
  }

  returnData($mode);

?>
