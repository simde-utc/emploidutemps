<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/include.php');

  $id = 1;

  function arrayRecue($login, $recue, $passed, $bgColor, $interraction) {
    $recueInfo = getUVFromIdUV($recue['idUV']);
    $nbrSameTime = count(array_keys($passed, array($recueInfo['jour'], $recueInfo['debut'], $recueInfo['fin'])));
    $exchange = getUVFromIdUV($recue['pour']);
    // Conversion de minutes en heures
    $exploded = explode(':', $recueInfo['debut']);
    $debut = join('.', array($exploded[0], 50/30*$exploded[1]));
    $exploded = explode(':', $recueInfo['fin']);
    $fin = join('.', array($exploded[0], 50/30*$exploded[1]));

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
      'type' => ($recueInfo['type'] == 'D' ? $recueInfo['type'] = 'TD' : ($recueInfo['type'] == 'C' ? $recueInfo['type'] = 'Cours' : $recueInfo['type'] = 'TP')),
      'groupe' => $recueInfo['groupe'],
      'salle' => $recueInfo['salle'],
      'frequence' => $recueInfo['frequence'],
      'semaine' => $recueInfo['semaine'],
      'note' => (($recueInfo['semaine'] == '') ? '' : 'Sem '.$recueInfo['semaine']),
      'fgColor' => $fgColor,
      'bgColor' => $bgColor,
      'nbrSameTime' => $nbrSameTime,
      'columnPerDay' => 2,
      'interraction' => '<div onClick=\'infosExchange('.$recue['idEchange'].');\'>En échange avec le vôtre du '.$GLOBALS['jours'][$exchange['jour']].' de '.$exchange['debut'].' à '.$exchange['fin'].' en '.$exchange['salle'].(($exchange['semaine'] == '') ? '' : ' chaque semaine '.$exchange['semaine']).'</div>'.$interraction,
      'session' => 0
    );
  }

  function printRecues($login) {
    $recues = getRecuesList($login, NULL, 1);
    $recuesRefused = getRecuesList($login, NULL, 0, 0);
    $recuesAccepted = getRecuesList($login, NULL, 0, 1);
    $arraydemande = array();
    $passed = array();

    foreach ($recues as $recue) {
      $recueInfo = getUVFromIdUV($recue['idUV']);
      array_push($passed, array($recueInfo['jour'], $recueInfo['debut'], $recueInfo['fin']));
    }

    foreach ($recuesRefused as $recue) {
      $recueInfo = getUVFromIdUV($recue['idUV']);
      array_push($passed, array($recueInfo['jour'], $recueInfo['debut'], $recueInfo['fin']));
    }

    foreach ($recuesAccepted as $recue) {
      $recueInfo = getUVFromIdUV($recue['idUV']);
      array_push($passed, array($recueInfo['jour'], $recueInfo['debut'], $recueInfo['fin']));
    }

    foreach ($recues as $recue) {
      if ($recue['active'] == 1)
        array_push($arraydemande, arrayRecue($login, $recue, $passed, '#0000FF', '<button class="option" style="width: 59px; height: 15px" onClick=\'acceptExchange('.$recue['idEchange'].');\'>Accepter</button><button class="option" style="width: 59px; height: 15px" onClick=\'refuseExchange('.$recue['idEchange'].');\'>Refuser</button>'));
      else
        array_push($arraydemande, arrayRecue($login, $recue, $passed, '#FF0000', '<button class="option" onClick=\'askForExchange('.$recue['pour'].', '.$recue['idUV'].');\'>Faire la proposition</button>'));
    }

    foreach ($recuesRefused as $recue)
      array_push($arraydemande, arrayRecue($login, $recue, $passed, '#FF0000', '<button class="option" style="background-color: #777777; color: #FFFFFF" disabled\'>Proposition réfusée</button>'));

    foreach ($recuesAccepted as $recue)
      array_push($arraydemande, arrayRecue($login, $recue, $passed, '#00FF00', '<button class="option" style="background-color: #777777; color: #FFFFFF" disabled\'>Proposition acceptée</button>'));

    return $arraydemande;
  }

  function arrayEnvoie($login, $envoie, $passed, $bgColor, $interraction) {
    $envoieInfo = getUVFromIdUV($envoie['idUV']);
    $nbrSameTime = count(array_keys($passed, array($envoieInfo['jour'], $envoieInfo['debut'], $envoieInfo['fin'])));
    $exchange = getUVFromIdUV($envoie['pour']);
    // Conversion de minutes en heures
    $exploded = explode(':', $envoieInfo['debut']);
    $debut = join('.', array($exploded[0], 50/30*$exploded[1]));
    $exploded = explode(':', $envoieInfo['fin']);
    $fin = join('.', array($exploded[0], 50/30*$exploded[1]));

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
      'type' => ($envoieInfo['type'] == 'D' ? $envoieInfo['type'] = 'TD' : ($envoieInfo['type'] == 'C' ? $envoieInfo['type'] = 'Cours' : $envoieInfo['type'] = 'TP')),
      'groupe' => $envoieInfo['groupe'],
      'salle' => $envoieInfo['salle'],
      'frequence' => $envoieInfo['frequence'],
      'semaine' => $envoieInfo['semaine'],
      'note' => (($envoieInfo['semaine'] == '') ? '' : 'Sem '.$envoieInfo['semaine']),
      'fgColor' => $fgColor,
      'bgColor' => $bgColor,
      'nbrSameTime' => $nbrSameTime,
      'columnPerDay' => 2,
      'interraction' => '<div onClick=\'infosExchange('.$envoie['idEchange'].');\'>En échange avec celui du '.$GLOBALS['jours'][$exchange['jour']].' de '.$exchange['debut'].' à '.$exchange['fin'].' en '.$exchange['salle'].(($exchange['semaine'] == '') ? '' : ' chaque semaine '.$exchange['semaine']).'</div>'.$interraction,
      'session' => 0
    );
  }

  function printEnvoies($login) {
    $envoies = getEnvoiesList($login, NULL, 1);
    $envoiesRefused = getEnvoiesList($login, NULL, 0, 0);
    $envoiesAccepted = getEnvoiesList($login, NULL, 0, 1);
    $arraydemande = array();
    $passed = array();

    foreach ($envoies as $envoie) {
      $envoieInfo = getUVFromIdUV($envoie['idUV']);
      array_push($passed, array($envoieInfo['jour'], $envoieInfo['debut'], $envoieInfo['fin']));
    }

    foreach ($envoiesRefused as $envoie) {
      $envoieInfo = getUVFromIdUV($envoie['idUV']);
      array_push($passed, array($envoieInfo['jour'], $envoieInfo['debut'], $envoieInfo['fin']));
    }

    foreach ($envoiesAccepted as $envoie) {
      $envoieInfo = getUVFromIdUV($envoie['idUV']);
      array_push($passed, array($envoieInfo['jour'], $envoieInfo['debut'], $envoieInfo['fin']));
    }

    foreach ($envoies as $envoie)
      array_push($arraydemande, arrayEnvoie($login, $envoie, $passed, '#0000FF', '<button class="option" onClick=\'delExchange('.$envoie['idEchange'].');\'>Retirer la proposition</button>'));

    foreach ($envoiesRefused as $envoie)
      array_push($arraydemande, arrayEnvoie($login, $envoie, $passed, '#FF0000', '<button class="option" disabled\'>Proposition réfusée</button>'));

    foreach ($envoiesAccepted as $envoie)
      array_push($arraydemande, arrayEnvoie($login, $envoie, $passed, '#00FF00', '<button class="option" disabled\'>Proposition acceptée</button>'));

    return $arraydemande;
  }

  function printManyEdtEtu($logins) {
    $arrayEdt = array();

    $loginList = join('", "', $logins);
    $query = $GLOBALS['bdd']->query('SELECT login, uv, salle, jour, debut, fin, semaine FROM uvs, cours WHERE login IN ("'.$loginList.'") AND (frequence = 1 OR frequence = 2) AND uvs.id = cours.id ORDER BY jour, debut, fin DESC');

    $result = $query->fetchAll();

    foreach ($result as $edt) {

      // Conversion de minutes en heures
      $exploded = explode(':', $edt['debut']);
      $debut = join('.', array($exploded[0], 50/30*$exploded[1]));
      $exploded = explode(':', $edt['fin']);
      $fin = join('.', array($exploded[0], 50/30*$exploded[1]));

      if ($edt['login'] == $_SESSION['login'])
        $bgColor = '#770000';
      else
        $bgColor = $GLOBALS['colors'][array_search($edt['login'], $_SESSION['etuActive']) % count($GLOBALS['colors'])];

      array_push($arrayEdt, array(
        'id' => $GLOBALS['id']++,
        'idUV' => $edt['uv'],
        'salle' => $edt['salle'],
        'login' => $edt['login'],
        'column' => $edt['jour'],
        'duration' => $fin - $debut,
        'startTime' => $debut - 7,
        'semaine' => $edt['semaine'],
        'fgColor' => '',
        'bgColor' => $bgColor,
        'columnPerDay' => 3,
        'session' => ($edt['login'] == $_SESSION['login'])
      ));
    }

    return $arrayEdt;
  }


  function printEdtEtu($login, $columnPerDay = 0, $actuel = 1, $echange = NULL) {
    $allEdt = getEdtEtu($login, $actuel, $echange);
    $arrayEdt = array();
    $passed = array();

    foreach ($allEdt as $edt)
      array_push($passed, array($edt['jour'], $edt['debut'], $edt['fin']));

    foreach ($allEdt as $edt) {
      $nbrSameTime = count(array_keys($passed, array($edt['jour'], $edt['debut'], $edt['fin'])));
      // Conversion de minutes en heures
      $exploded = explode(':', $edt['debut']);
      $debut = join('.', array($exploded[0], 50/30*$exploded[1]));
      $exploded = explode(':', $edt['fin']);
      $fin = join('.', array($exploded[0], 50/30*$exploded[1]));

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
        'type' => ($edt['type'] == 'D' ? $edt['type'] = 'TD' : ($edt['type'] == 'C' ? $edt['type'] = 'Cours' : $edt['type'] = 'TP')),
        'groupe' => $edt['groupe'],
        'salle' => $edt['salle'],
        'frequence' => $edt['frequence'],
        'semaine' => $edt['semaine'],
        'note' => (($edt['semaine'] == '') ? '' : 'Sem '.$edt['semaine']),
        'fgColor' => $fgColor,
        'bgColor' => $bgColor,
        'nbrSameTime' => $nbrSameTime,
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
      $exploded = explode(':', $edt['debut']);
      $debut = join('.', array($exploded[0], 50/30*$exploded[1]));
      $exploded = explode(':', $edt['fin']);
      $fin = join('.', array($exploded[0], 50/30*$exploded[1]));

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
        'type' => ($edt['type'] == 'D' ? $edt['type'] = 'TD' : ($edt['type'] == 'C' ? $edt['type'] = 'Cours' : $edt['type'] = 'TP')),
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
    elseif (isset($_GET['recu']) && is_string($_GET['recu']) && $_GET['recu'] == '1') {
      $all = array_merge($all, printEdtEtu($_SESSION['login'], 1));
      $all = array_merge($all, printRecues($_SESSION['login']));
    }
    elseif (isset($_GET['envoi']) && is_string($_GET['envoi']) && $_GET['envoi'] == '1') {
      $all = array_merge($all, printEdtEtu($_SESSION['login'], 1));
      $all = array_merge($all, printEnvoies($_SESSION['login']));
    }
    else {
      $all = array_merge($all, printEdtEtu($_SESSION['login'], 1));

      if (isset($_GET['uv']) && is_string($_GET['uv']) && !empty($_GET['uv']))
        $all = array_merge($all, printEdtUV($_GET['uv'], 2, (isset($_GET['type']) && is_string($_GET['type']) && !empty($_GET['type']) ? $_GET['type'] : NULL)));
    }
  }
  elseif ($mode == 'organiser') {
    $all = array_merge(printManyEdtEtu(array_merge($_SESSION['etuActive'], array($_SESSION['login']))));
  }
  else {
    if (isset($_GET['uv']) && is_string($_GET['uv']) && !empty($_GET['uv']))
      $all = array_merge($all, printEdtUV($_GET['uv'], 0, (isset($_GET['type']) && is_string($_GET['type']) && !empty($_GET['type']) ? $_GET['type'] : NULL)));
    else
      $all = array_merge($all, printEdtEtu(isset($_GET['login']) && is_string($_GET['login']) && !empty($_GET['login']) ? $_GET['login'] : $_SESSION['login']));
  }


  echo json_encode($all);
?>
