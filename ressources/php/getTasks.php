<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/include.php');

  $id = 1;

  function arrayRecue($login, $recue, $bgColor, $interraction) {
    $recueInfo = getUVFromIdUV($recue['idUV']);
    $exchange = getUVFromIdUV($recue['pour']);
    // Conversion de minutes en heures
    $exploded = explode(':', $recueInfo['debut']);
    $debut = join('.', array($exploded[0], 100/60*$exploded[1]));
    $exploded = explode(':', $recueInfo['fin']);
    $fin = join('.', array($exploded[0], 100/60*$exploded[1]));

    $fgColor = getFgColor($bgColor);

    return array(
      'id' => 'r'.$recue['idEchange'],
      'login' => $login,
      'column' => $recueInfo['jour'],
      'duration' => $fin - $debut,
      'startTime' => $debut - 7,
      'horaire' => $recueInfo['debut'].'-'.$recueInfo['fin'],
      'idUV' => $recue['idUV'],
      'uv' => $recueInfo['uv'],
      'type' => ($recueInfo['type'] == 'D' ? 'TD' : ($recueInfo['type'] == 'C' ? 'Cours' : 'TP')),
      'groupe' => $recueInfo['groupe'],
      'salle' => $recueInfo['salle'],
      'frequence' => $recueInfo['frequence'],
      'semaine' => $recueInfo['semaine'],
      'note' => (($recueInfo['semaine'] == '') ? '' : 'Sem '.$recueInfo['semaine']),
      'fgColor' => $fgColor,
      'bgColor' => $bgColor,
      'columnPerDay' => 2,
      'interraction' => '<div class="infosExchange" onClick=\'infosExchange('.$recue['idEchange'].');\'>En échange avec le vôtre du '.$GLOBALS['jours'][$exchange['jour']].' de '.$exchange['debut'].' à '.$exchange['fin'].' en '.$exchange['salle'].(($exchange['semaine'] == '') ? '' : ' chaque semaine '.$exchange['semaine']).'</div>'.$interraction,
      'session' => 0
    );
  }

  function printRecues($login, $stat = '1') {
    $recues = array();
    $recuesRefused = array();
    $recuesAccepted = array();
    $recuesCanceled = array();
    $arraydemande = array();

    if ($stat == '1') {
      $recues = getRecuesList($login, NULL, 1, 0);
      $recuesRefused = getRecuesList($login, NULL, 0, 0);
      $recuesAccepted = getRecuesList($login, NULL, 0, 1);
      $recuesCanceled = getRecuesList($login, NULL, 1, 1);
    }
    elseif ($stat == 'nouveau')
      $recues = getRecuesList($login, NULL, 1, 0);
    elseif ($stat == 'accepte') {
      $recuesAccepted = getRecuesList($login, NULL, 0, 1);
      $recuesCanceled = getRecuesList($login, NULL, 1, 1);
    }
    elseif ($stat == 'refuse')
      $recuesRefused = getRecuesList($login, NULL, 0, 0);

    foreach ($recues as $recue) {
      if ($recue['active'] == 1)
        array_push($arraydemande, arrayRecue($login, $recue, '#0000FF', '<button class="option" style="width: 59px; height: 15px" onClick=\'acceptExchange('.$recue['idEchange'].');\'>Accepter</button><button class="option" style="width: 59px; height: 15px" onClick=\'refuseExchange('.$recue['idEchange'].');\'>Refuser</button>'));
      else
        array_push($arraydemande, arrayRecue($login, $recue, '#FFFF00', '<button class="option" onClick=\'askForExchange('.$recue['pour'].', '.$recue['idUV'].');\'>Plus dispo. Proposer</button>'));
    }

    foreach ($recuesRefused as $recue)
      array_push($arraydemande, arrayRecue($login, $recue, '#FF0000', '<button class="option" disabled>Proposition refusée</button>'));

    foreach ($recuesAccepted as $recue)
      array_push($arraydemande, arrayRecue($login, $recue, '#00FF00', '<button class="option" onClick=\'cancelExchange('.$recue['idEchange'].');\'>Annuler l\'échange</button>'));

    foreach ($recuesCanceled as $recue)
      array_push($arraydemande, arrayRecue($login, $recue, '#555555', '<button class="option" style="background-color: #777777; color: #FFFFFF" disabled>Annulation demandée</button>'));

    return $arraydemande;
  }

  function arrayEnvoie($login, $envoie, $bgColor, $interraction) {
    $envoieInfo = getUVFromIdUV($envoie['idUV']);
    $exchange = getUVFromIdUV($envoie['pour']);
    // Conversion de minutes en heures
    $exploded = explode(':', $envoieInfo['debut']);
    $debut = join('.', array($exploded[0], 100/60*$exploded[1]));
    $exploded = explode(':', $envoieInfo['fin']);
    $fin = join('.', array($exploded[0], 100/60*$exploded[1]));

    $fgColor = getFgColor($bgColor);

    return array(
      'id' => 'e'.$envoie['idEchange'],
      'login' => $login,
      'column' => $envoieInfo['jour'],
      'duration' => $fin - $debut,
      'startTime' => $debut - 7,
      'horaire' => $envoieInfo['debut'].'-'.$envoieInfo['fin'],
      'idUV' => $envoie['idUV'],
      'uv' => $envoieInfo['uv'],
      'type' => ($envoieInfo['type'] == 'D' ? 'TD' : ($envoieInfo['type'] == 'C' ? 'Cours' : 'TP')),
      'groupe' => $envoieInfo['groupe'],
      'salle' => $envoieInfo['salle'],
      'frequence' => $envoieInfo['frequence'],
      'semaine' => $envoieInfo['semaine'],
      'note' => (($envoieInfo['semaine'] == '') ? '' : 'Sem '.$envoieInfo['semaine']),
      'fgColor' => $fgColor,
      'bgColor' => $bgColor,
      'columnPerDay' => 2,
      'interraction' => '<div class="infosExchange" onClick=\'infosExchange('.$envoie['idEchange'].');\'>En échange avec celui du '.$GLOBALS['jours'][$exchange['jour']].' de '.$exchange['debut'].' à '.$exchange['fin'].' en '.$exchange['salle'].(($exchange['semaine'] == '') ? '' : ' chaque semaine '.$exchange['semaine']).'</div>'.$interraction,
      'session' => 0
    );
  }

  function printEnvoies($login, $stat = '1') {
    $envoies = array();
    $envoiesRefused = array();
    $envoiesAccepted = array();
    $envoiesCanceled = array();
    $arraydemande = array();

    if ($stat == '1') {
      $envoies = getEnvoiesList($login, NULL, 1, 0);
      $envoiesRefused = getEnvoiesList($login, NULL, 0, 0);
      $envoiesAccepted = getEnvoiesList($login, NULL, 0, 1);
      $envoiesCanceled = getEnvoiesList($login, NULL, 1, 1);
    }
    elseif ($stat == 'nouveau')
      $envoies = getEnvoiesList($login, NULL, 1, 0);
    elseif ($stat == 'accepte') {
      $envoiesAccepted = getEnvoiesList($login, NULL, 0, 1);
      $envoiesCanceled = getEnvoiesList($login, NULL, 1, 1);
    }
    elseif ($stat == 'refuse')
      $envoiesRefused = getEnvoiesList($login, NULL, 0, 0);

    foreach ($envoies as $envoie)
      array_push($arraydemande, arrayEnvoie($login, $envoie, '#0000FF', '<button class="option" onClick=\'delExchange('.$envoie['idEchange'].');\'>Annuler la proposition</button>'));

    foreach ($envoiesRefused as $envoie)
      array_push($arraydemande, arrayEnvoie($login, $envoie, '#FF0000', '<button class="option" disabled>Proposition réfusée</button>'));

    foreach ($envoiesAccepted as $envoie)
      array_push($arraydemande, arrayEnvoie($login, $envoie, '#00FF00', '<button class="option" onClick=\'cancelExchange('.$envoie['idEchange'].');\'>Annuler l\'échange</button>'));

    foreach ($envoiesCanceled as $envoie)
      array_push($arraydemande, arrayEnvoie($login, $envoie, '#555555', '<button class="option" style="background-color: #777777; color: #FFFFFF" disabled>Annulation demandée</button>'));

    return $arraydemande;
  }

  function printAnnulees($login) {
    $demandesCanceled = getAnnulationList($login); // Demandes d'annulation reçues
    $recuesCanceled = getRecuesList($login, NULL, 1, 1);
    $envoiesCanceled = getEnvoiesList($login, NULL, 1, 1);
    $arraydemande = array();

    foreach ($demandesCanceled as $demande)
      array_push($arraydemande, arrayRecue($login, $demande, $passed, '#FF00FF', '<button class="option" onClick=\'cancelExchange('.$demande['idEchange'].');\'>Accepter l\'annulation</button>'));

    foreach ($recuesCanceled as $recue)
      array_push($arraydemande, arrayRecue($login, $recue, $passed, '#555555', '<button class="option" style="background-color: #777777; color: #FFFFFF" disabled>Annulation demandée</button>'));

    foreach ($envoiesCanceled as $envoie)
      array_push($arraydemande, arrayEnvoie($login, $envoie, $passed, '#555555', '<button class="option" style="background-color: #777777; color: #FFFFFF" disabled>Annulation demandée</button>'));

    return $arraydemande;
  }

  function printManyEdtEtu($logins, $week) {
    $arrayEdt = array();
    $edts = array();

    foreach ($logins as $login) {
      $edts = getWeekEdt($login, $week);

      foreach ($edts as $edt) {
        // Conversion de minutes en heures
        $exploded = explode(':', $edt['debut'], 2);
        $debut = join('.', array($exploded[0], 100/60*$exploded[1]));
        $exploded = explode(':', $edt['fin'], 2);
        $fin = join('.', array($exploded[0], 100/60*$exploded[1]));

        if ($edt['type'] == 'calendar' && isset($edt['color']))
          $bgColor = $edt['color'];
        elseif ($login == $_SESSION['login'])
          $bgColor = '#770000';
        else
          $bgColor = $GLOBALS['colors'][array_search($login, $_SESSION['etuActive']) % count($GLOBALS['colors'])];

        array_push($arrayEdt, array(
          'id' => $GLOBALS['id']++,
          'idUV' => $edt['uv'],
          'salle' => $edt['salle'],
          'login' => $edt['login'],
          'column' => $edt['jour'],
          'duration' => $fin - $debut,
          'startTime' => $debut - 7,
          'semaine' => $edt['semaine'],
          'fgColor' => '#FFFFFF',
          'bgColor' => $bgColor,
          'columnPerDay' => 3,
          'session' => ($edt['login'] == $_SESSION['login'])
        ));
      }
    }

    return $arrayEdt;
  }


  function printEdtEtu($login, $columnPerDay = 0, $actuel = 1, $echange = NULL) {
    $allEdt = getEdtEtu($login, $actuel, $echange);
    $arrayEdt = array();

    foreach ($allEdt as $edt) {
      // Conversion de minutes en heures
      $exploded = explode(':', $edt['debut'], 2);
      $debut = join('.', array($exploded[0], 100/60*$exploded[1]));
      $exploded = explode(':', $edt['fin'], 2);
      $fin = join('.', array($exploded[0], 100/60*$exploded[1]));

      $bgColor = ($edt['color'] == NULL ? $edt['colorUV'] : $edt['color']);

      if ($actuel == 0 && $echange == 1)
        $bgColor = '#FF0000';
      elseif ($actuel == 1 && $echange == 1)
        $bgColor = '#00FF00';

      $fgColor = getFgColor($bgColor);

      array_push($arrayEdt, array(
        'id' => $GLOBALS['id']++,
        'login' => $login,
        'column' => $edt['jour'],
        'duration' => $fin - $debut,
        'startTime' => $debut - 7,
        'horaire' => $edt['debut'].'-'.$edt['fin'],
        'idUV' => $edt['id'],
        'uv' => $edt['uv'],
        'type' => ($edt['type'] == 'D' ? 'TD' : ($edt['type'] == 'C' ? 'Cours' : 'TP')),
        'groupe' => $edt['groupe'],
        'salle' => $edt['salle'],
        'frequence' => $edt['frequence'],
        'semaine' => $edt['semaine'],
        'note' => (($edt['semaine'] == '') ? '' : 'Sem '.$edt['semaine']),
        'fgColor' => $fgColor,
        'bgColor' => $bgColor,
        'columnPerDay' => $columnPerDay,
        'session' => ($login == $_SESSION['login'])
      ));
    }

    return $arrayEdt;
  }


  function getWeekEdt($login, $week, $getEdt = 'getEdtEtu', $nbrOfDays = 7) {
    $days = getDays($week, $nbrOfDays);
    $allEdt = array();
    $columnPerDay = 0;

    foreach ($days as $i => $day) {
      $dayEdt = $getEdt($login, 1, NULL, $day['jour']);

      if ($day['infos'] != '') {
        $split = explode(' - ', $day['infos']);
        $summary = (isset($split[0]) ? $split[0] : $day['infos']);
        $description = (isset($split[1]) ? $split[1] : '');
        $location = (isset($split[2]) ? $split[2] : '');
        array_push($allEdt, array('uv' => $summary, 'note' => $description, 'idUV' => NULL, 'jour' => $i, 'debut' => '00:00', 'fin' => '23:59', 'type' => 'calendar', 'groupe' => '', 'salle' => $location, 'color' => '#000000'));
      }
      else {
        $query = $GLOBALS['bdd']->prepare('SELECT * FROM days WHERE begin < ? AND end >= ? ORDER BY end DESC');
        $GLOBALS['bdd']->execute($query, array($day['date'], $day['date']));

        if ($query->rowCount() == 1) {
          $data = $query->fetch();
          $split = explode(' - ', $data['infos']);
          $summary = (isset($split[0]) ? $split[0] : $data['infos']);
          $description = (isset($split[1]) ? $split[1] : '');
          $location = (isset($split[2]) ? $split[2] : '');
          array_push($allEdt, array('uv' => $summary, 'note' => $description, 'idUV' => NULL, 'jour' => $i, 'debut' => '00:00', 'fin' => '23:59', 'type' => '', 'groupe' => '', 'salle' => $location, 'color' => '#000000'));
        }
      }

      foreach ($dayEdt as $edt) {
        if (($edt['type'] == 'C' && $day['cours']) || ($edt['type'] == 'D' && $day['td']) || ($edt['type'] == 'T' && $day['tp']) || ($edt['type'] == '' && ($day['cours'] || $day['td']))) {
          if ($edt['semaine'] != '') {
            if (($day['semaine'] === 'A' && $edt['semaine'] === 'B') || ($day['semaine'] === 'B' && $edt['semaine'] === 'A'))
              continue;

            $edt['note'] = 'Cette semaine A';
          }

          $edt['jour'] = $i;
          array_push($allEdt, $edt);
        }
      }
    }

    return $allEdt;
  }


  function printWeek($login, $week, $getEdt = 'getEdtEtu', $nbrOfDays = 7) {
    $allEdt = getWeekEdt($login, $week, $getEdt, $nbrOfDays);
    $arrayEdt = array();

    foreach ($allEdt as $i => $edt) {
      // Conversion de minutes en heures
      $exploded = explode(':', $edt['debut'], 2);
      $debut = join('.', array($exploded[0], 100/60*$exploded[1]));
      $exploded = explode(':', $edt['fin'], 2);
      $fin = join('.', array($exploded[0], 100/60*$exploded[1]));

      $bgColor = (isset($edt['color']) ? ($edt['color'] == NULL ? $edt['colorUV'] : $edt['color']) : (isset($edt['colorUV']) ? $edt['colorUV'] : $GLOBALS['colors'][$GLOBALS['id'] % count($GLOBALS['colors'])]));
      $fgColor = getFgColor($bgColor);

      array_push($arrayEdt, array(
        'id' => $GLOBALS['id']++,
        'login' => $login,
        'column' => $edt['jour'],
        'duration' => $fin - $debut,
        'startTime' => $debut - 7,
        'horaire' => $edt['debut'].'-'.$edt['fin'],
        'idUV' => $edt['id'],
        'uv' => $edt['uv'],
        'type' => ($edt['type'] == 'D' ? 'TD' : ($edt['type'] == 'C' ? 'Cours' : ($edt['type'] == 'T' ? 'TP' : ''))),
        'groupe' => $edt['groupe'],
        'salle' => $edt['salle'],
        'frequence' => '1',
        'semaine' => '',
        'note' => ((isset($edt['note'])) ? $edt['note'] : ''),
        'fgColor' => $fgColor,
        'bgColor' => $bgColor,
        'columnPerDay' => $columnPerDay,
        'session' => ($login == $_SESSION['login'])
      ));
    }

    return $arrayEdt;
  }


  function printEdtUV($uv, $columnPerDay = 0, $type = NULL, $userColor = NULL) {
    $allEdt = getEdtUV($uv, $type);
    $arrayEdt = array();
    $passed = array();

    foreach ($allEdt as $edt)
      array_push($passed, array($edt['jour'], $edt['debut'], $edt['fin']));

    foreach ($allEdt as $edt) {
      $nbrSameTime = count(array_keys($passed, array($edt['jour'], $edt['debut'], $edt['fin'])));

      // Conversion de minutes en heures
      $exploded = explode(':', $edt['debut'], 2);
      $debut = join('.', array($exploded[0], 100/60*$exploded[1]));
      $exploded = explode(':', $edt['fin'], 2);
      $fin = join('.', array($exploded[0], 100/60*$exploded[1]));

      $query = $GLOBALS['bdd']->prepare('SELECT color FROM uvs, cours WHERE uvs.uv = ? AND cours.login = ? AND uvs.id = cours.id LIMIT 1');
      $GLOBALS['bdd']->execute($query, array($uv, $_SESSION['login']));

      if ($query->rowCount() == 0)
        $bgColor = $edt['color'];
      else {
        $data = $query->fetch();
        $bgColor = $data['color'];

        if ($bgColor == NULL)
          $bgColor = $edt['color'];
      }

      $fgColor = getFgColor($bgColor);

      array_push($arrayEdt, array(
        'id' => $GLOBALS['id']++,
        'column' => $edt['jour'],
        'duration' => $fin - $debut,
        'startTime' => $debut - 7,
        'horaire' => $edt['debut'].'-'.$edt['fin'],
        'idUV' => $edt['id'],
        'uv' => $edt['uv'],
        'type' => ($edt['type'] == 'D' ? 'TD' : ($edt['type'] == 'C' ? 'Cours' : 'TP')),
        'groupe' => $edt['groupe'],
        'salle' => $edt['salle'],
        'frequence' => $edt['frequence'],
        'semaine' => $edt['semaine'],
        'note' => (($edt['semaine'] == '') ? '' : 'Sem '.$edt['semaine'].'<br>').$edt['nbrEtu'].' étudiants',
        'fgColor' => $fgColor,
        'bgColor' => $bgColor,
        'nbrSameTime' => $nbrSameTime,
        'columnPerDay' => $columnPerDay,
        'session' => FALSE
      ));
    }

    return $arrayEdt;
  }


  $all = array();

  if (isset($_GET['mode']) && is_string($_GET['mode']) && !empty($_GET['mode']))
    $mode = $_GET['mode'];
  else
    $mode = 'afficher';

  if ($mode == 'comparer') {
    $all = array_merge($all, printEdtEtu($_SESSION['login'], 1));

    if (isset($_GET['login']) && is_string($_GET['login']) && !empty($_GET['login']) && $_GET['login'] != $_SESSION['login'])
      $all = array_merge($all, printEdtEtu($_GET['login'], 2));
    elseif (isset($_GET['uv']) && is_string($_GET['uv']) && !empty($_GET['uv']))
      $all = array_merge($all, printEdtUV($_GET['uv'], 2));
  }
  elseif ($mode == 'modifier') {
    if (isset($_GET['original']) && is_string($_GET['original']) && $_GET['original'] == '1') {
      $all = array_merge($all, printEdtEtu($_SESSION['login'], 0, 1, 0));
      $all = array_merge($all, printEdtEtu($_SESSION['login'], 0, 0, 1));
    }
    elseif (isset($_GET['changement']) && is_string($_GET['changement']) && $_GET['changement'] == '1') {
      $all = array_merge($all, printEdtEtu($_SESSION['login'], 1, 1));
      $all = array_merge($all, printEdtEtu($_SESSION['login'], 2, 0, 1));
      $all = array_merge($all, printEdtEtu($_SESSION['login'], 2, 1, 1));
    }
    elseif (isset($_GET['recu']) && is_string($_GET['recu'])) {
      $all = array_merge($all, printEdtEtu($_SESSION['login'], 1));
      $all = array_merge($all, printRecues($_SESSION['login'], $_GET['recu']));
    }
    elseif (isset($_GET['envoi']) && is_string($_GET['envoi'])) {
      $all = array_merge($all, printEdtEtu($_SESSION['login'], 1));
      $all = array_merge($all, printEnvoies($_SESSION['login'], $_GET['envoi']));
    }
    elseif (isset($_GET['annule']) && is_string($_GET['annule']) && $_GET['annule'] == '1') {
      $all = array_merge($all, printEdtEtu($_SESSION['login'], 1));
      $all = array_merge($all, printAnnulees($_SESSION['login']));
    }
    else {
      $all = array_merge($all, printEdtEtu($_SESSION['login'], 1));

      if (isset($_GET['uv']) && is_string($_GET['uv']) && !empty($_GET['uv']))
        $all = array_merge($all, printEdtUV($_GET['uv'], 2, (isset($_GET['type']) && is_string($_GET['type']) && !empty($_GET['type']) ? $_GET['type'] : NULL)));
    }
  }
  elseif ($mode == 'organiser') {
    $all = array_merge($all, printManyEdtEtu(array_merge($_SESSION['etuActive'], array($_SESSION['login'])), $_SESSION['week']));
  }
  elseif ($mode == 'planifier') {
    if (isset($_GET['cours']) && $_GET['cours'] = '1')
      $all = array_merge($all, printWeek($_SESSION['login'], $_SESSION['week']));
    elseif (isset($_GET['event']) && $_GET['event'] = '1')
      $all = array_merge($all, printWeek($_SESSION['login'], $_SESSION['week'], 'getEdtEvent'));
    elseif (isset($_GET['reu']) && $_GET['reu'] = '1')
      $all = array_merge($all, printWeek($_SESSION['login'], $_SESSION['week'], 'getEdtReu'));
    elseif (isset($_GET['salle']) && is_string($_GET['salle'])  && !empty($_GET['salle']))
      $all = array_merge($all, printWeek($_GET['salle'], $_SESSION['week'], 'getEdtSalle'));
    else {
      $all = array_merge($all, printWeek($_SESSION['login'], $_SESSION['week']));
      //$all = array_merge($all, printWeek($_SESSION['login'], $_SESSION['week'], 'getEdtReu'));
    }
  }
  elseif ($mode == 'test') {
    $all = array_merge($all, printManyEdtEtu(array_merge($_SESSION['etuActive'], array($_SESSION['login'])), $_SESSION['week']));
  }
  else {
    if (isset($_GET['uv']) && is_string($_GET['uv']) && !empty($_GET['uv']))
      $all = array_merge($all, printEdtUV($_GET['uv'], 0, (isset($_GET['type']) && is_string($_GET['type']) && !empty($_GET['type']) ? $_GET['type'] : NULL)));
    else
      $all = array_merge($all, printEdtEtu(isset($_GET['login']) && is_string($_GET['login']) && !empty($_GET['login']) ? $_GET['login'] : $_SESSION['login']));
  }


  echo json_encode($all);
?>
