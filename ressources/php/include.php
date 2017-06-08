<?php
  date_default_timezone_set('Europe/Paris');
  mb_internal_encoding("UTF-8");
  session_start();

  ini_set('display_errors', 1);  ini_set('display_startup_errors', 1);  error_reporting(E_ALL);

  $etuPic = '<i class="searchImg fa fa-4x fa-user-o" style="padding-left: 1px; padding-top: 3px;" aria-hidden="true"></i>';
  $uvPic = '<i class="searchImg fa fa-4x fa-graduation-cap" style="margin-left:10%;" aria-hidden="true"></i>';
  $colors = array('#7DC779', '#82A1CA', '#F2D41F', '#457293', '#AB7AC6', '#DF6F53', '#B0CEE9', '#AAAAAA', '#1C704E');
  $jours = array('lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche');
  include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/class/class.bdd.php');
  include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/class/class.curl.php');
  include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/class/class.cas.php');

  if (isset($_GET['MODCASID']) && is_string($_GET['MODCASID']))
    $_SESSION['MODCASID'] = $_GET['MODCASID'];

  $bdd = new BDD();

  function shutdown() {
    $GLOBALS['bdd'] = null;
  }
  register_shutdown_function('shutdown');

  function getTabs() {
    $tabs = array();

    $query = $GLOBALS['bdd']->prepare('SELECT * FROM students_tabs WHERE login = ?');
    $GLOBALS['bdd']->execute($query, array($_SESSION['login']));
    $data = $query->fetchAll();

    foreach ($data as $tab) {
      $tabs[$tab['name']] = array();

      $query = $GLOBALS['bdd']->prepare('SELECT * FROM students_tabs_groups WHERE idTab = ?');
      $GLOBALS['bdd']->execute($query, array($tab['id']));
      $groups = $query->fetchAll();

      foreach ($groups as $group) {
        $query = $GLOBALS['bdd']->prepare('SELECT * FROM students_tabs_elements WHERE idGroup = ?');
        $GLOBALS['bdd']->execute($query, array($group['id']));
        $elements = $query->fetchAll();

        foreach ($elements as $element)
          $tabs[$tab['name']][$group['name']][$element['element']] == $element['description'];
      }
    }

    return $tabs;
  }

  function setTabs() {
    $_SESSION['activeTabs'] = array();
    $_SESSION['tabs'] = array(
      'others' => array(
        'type' => 'others',
        'name' => 'Autres',
        'students' => array(),
        'uvs' => array()
      )
    );

    $tabs = getTabs();
    foreach ($tabs as $name => $tab) {
      $_SESSION['tabs'][$name] = array(
        'type' => 'custom'
      );

      foreach ($tabs as $key => $tab)
        $_SESSION['tabs'][$name][$key] = $tab;
    }

    $roles = end(json_decode(file_get_contents('http://assos.utc.fr/profile/'.$_SESSION['login'].'/json'), TRUE)['semestres'])['roles'];
    foreach ($roles as $role) {
      $asso = $role['asso'];
      $_SESSION['tabs'][$asso['login']] = array(
        'type' => 'asso',
        'name' => $asso['name'],
        'Bureaux' => array(),
        'Responsables' => array(),
        'Membres' => array()
      );

      $members = json_decode(file_get_contents('http://assos.utc.fr/asso/'.$asso['login'].'/json'), TRUE)['members'];
      foreach ($members as $member) {
          if (!$member['bureau'])
            $_SESSION['tabs'][$asso['login']]['Membres'][$member['login']] = $member['role'];
          elseif (preg_match('/Resp/', $member['role']))
            $_SESSION['tabs'][$asso['login']]['Responsables'][$member['login']] = $member['role'];
          else
            $_SESSION['tabs'][$asso['login']]['Bureaux'][$member['login']] = $member['role'];
      }
    }
  }

  function getStudentInfos($login = NULL) {
    $query = $GLOBALS['bdd']->prepare('SELECT * FROM students WHERE (? IS NULL OR login = ?)');
    $GLOBALS['bdd']->execute($query, array($login, $login));

    $length = $query->rowCount();
    $etus = $query->fetchAll();

    for($i = 0; $i < $length; $i++) {
      //$etus[$i]['uvs'] = substr($etus[$i]['uvs'], 0, -1);
      $etus[$i]['branch'] = substr($etus[$i]['semester'], 0, -2);
    }

    if ($length == 1)
      return $etus[0];
    else
      return $etus;
  }

  $curl = new CURL(strpos($_SERVER['HTTP_HOST'],'utc') !== false);
  if (isset($_SESSION['MODCASID']))
    $curl->setCookies('MODCASID='.$_SESSION['MODCASID']);

  if (!isset($_SESSION['login']) && !isset($api)) {
    if (!isset($_SESSION['_GET']))
      $_SESSION['_GET'] = $_GET;

  	$info = CAS::authenticate();

  	if ($info != -1) 	{
  		$_SESSION['login'] = $info['cas:user'];
      $_SESSION['email'] = $info['cas:attributes']['cas:mail'];
      $_SESSION['firstname'] = $info['cas:attributes']['cas:givenName'];
      $_SESSION['surname'] = strtoupper($info['cas:attributes']['cas:sn']);
  		$_SESSION['ticket'] = $_GET['ticket'];
      $infos = getStudentInfos($_SESSION['login']);
      $_SESSION['uvs'] = $infos['uvs'];
      setTabs();
      $_SESSION['week'] = (isset($_GET['week']) && is_string($_GET['week']) && isAGoodDate($_GET['week'])) ? $_GET['week'] : date('Y-m-d', strtotime('monday this week'));

      $get = '?';
      foreach ($_SESSION['_GET'] as $key => $value) {
        if ($key != 'ticket')
          $get .= $key.'='.$value.'&';
      }

      unset($_SESSION['_GET']);

      header('Location: /emploidutemps/'.substr($get, 0, -1));
      exit;
  	}
  	else
      CAS::login();
  }

  function sendMail($mail, $subject, $message, $from = 'emploidutemps@assos.utc.fr') {
    $query = $GLOBALS['bdd']->prepare('SELECT desinscrit FROM students WHERE mail = ?');
    $GLOBALS['bdd']->execute($query, array($mail));
    $data = $query->fetch();

    if ($data['desinscrit'] == '0')
      return mail($mail, $subject, $message.PHP_EOL.PHP_EOL.'Pour arrêter de recevoir des mails du service, tu peux à tout moment te désinscrire en cliquant ici: https://assos.utc.fr/emploidutemps/?param=sedesinscrire'.PHP_EOL.PHP_EOL.'En cas d\'erreur ou de bug, contacte-nous à cette adresse: simde@assos.utc.fr'.PHP_EOL.PHP_EOL.'Il y a une vie après les cours,'.PHP_EOL.'Le SiMDE', 'FROM:'.$from);

    return FALSE;
  }

  function isUpdating() {
    return $_SERVER['SCRIPT_NAME'] != '/emploidutemps/ressources/php/maj.php' && (file_exists($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/logs/'.'update') || file_exists($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/logs/'.'login'));
  }

  function getARandomColor() {
    return $GLOBALS['colors'][mt_rand(1, count($GLOBALS['colors'])) - 1];
  }

  function getExchangesReceived($login = NULL, $id = NULL, $idExchange = NULL, $available = NULL, $exchanged = NULL, $idUV = NULL, $idUV2 = NULL, $date = NULL, $idSent = NULL) {
    $query = $GLOBALS['bdd']->prepare(
      'SELECT exchanges_received.id, idExchange, login, exchanges.idUV, exchanges.idUV2, exchanges_received.date, exchanges_received.available, exchanges_received.exchanged, exchanges.enabled, idSent
      FROM exchanges_received, exchanges
      WHERE (? IS NULL OR login = ?) AND (? IS NULL OR exchanges_received.id = ?) AND (? IS NULL OR exchanges.idUV = ?) AND (? IS NULL OR exchanges.idUV2 = ?) AND (? IS NULL OR idExchange = ?)
      AND (? IS NULL OR exchanges_received.available = ?) AND (? IS NULL OR exchanges_received.exchanged = ?) AND (? IS NULL OR exchanges_received.date = ?) AND (? IS NULL OR idSent = ?)
      AND exchanges.id = exchanges_received.idExchange');
    $GLOBALS['bdd']->execute($query, array($login, $login, $id, $id, $idUV, $idUV, $idUV2, $idUV2, $idExchange, $idExchange, $available, $available, $exchanged, $exchanged, $date, $date, $idSent, $idSent));

    return $query->fetchAll();
  }

  function getExchanges($idUV, $idUV2, $enabled = NULL) {
    $query = $GLOBALS['bdd']->prepare(
      'SELECT id, enabled
      FROM exchanges
      WHERE idUV = ? AND idUV2 = ? AND (? IS NULL OR enabled = ?)');
    $GLOBALS['bdd']->execute($query, array($idUV, $idUV2, $enabled, $enabled));

    return $query->fetchAll();
  }

  function getExchangesSent($login = NULL, $id = NULL, $idExchange = NULL, $available = NULL, $exchanged = NULL, $idUV = NULL, $idUV2 = NULL, $date = NULL, $idReceived = NULL) {
    $query = $GLOBALS['bdd']->prepare(
      'SELECT exchanges_sent.id, idExchange, login, exchanges.idUV, exchanges.idUV2, exchanges_sent.date, exchanges_sent.note, exchanges_sent.available, exchanges_sent.exchanged, idReceived
      FROM exchanges_sent, exchanges
      WHERE (? IS NULL OR login = ?) AND (? IS NULL OR exchanges_sent.id = ?) AND (? IS NULL OR exchanges.idUV = ?) AND (? IS NULL OR exchanges.idUV2 = ?) AND (? IS NULL OR idExchange = ?)
      AND (? IS NULL OR exchanges_sent.available = ?) AND (? IS NULL OR exchanges_sent.exchanged = ?) AND (? IS NULL OR exchanges_sent.date = ?) AND (? IS NULL OR idReceived = ?)
      AND exchanges.id = exchanges_sent.idExchange
      ORDER BY date');
    $GLOBALS['bdd']->execute($query, array($login, $login, $id, $id, $idUV, $idUV, $idUV2, $idUV2, $idExchange, $idExchange, $available, $available, $exchanged, $exchanged, $date, $date, $idReceived, $idReceived));

    return $query->fetchAll();
  }

  function getExchangesCanceled($login) {
    $exchanges_canceled = array();
    $exchanges_sent = getExchangesSent($login, NULL, NULL, 0, 1); // On récupère tous nos échanges envoyés acceptés
    $exchanges_received = getExchangesReceived($login, NULL, NULL, 0, 1); // On récupère tous nos échanges recus acceptés

    foreach ($exchanges_sent as $exchange_sent) {
      $exchange_received = getExchangesReceived(NULL, $exchange_sent['idReceived'], $exchange_sent['idExchange'], 1, 1);

      if (count($exchange_received) == 1)
        array_push($exchanges_canceled, $exchange_received[0]);
    }

    foreach ($exchanges_received as $exchange_received) {
      $exchange_sent = getExchangesReceived(NULL, $exchange_received['idReceived'], $exchange_received['idExchange'], 1, 1);

      if (count($exchange_sent) == 1)
        array_push($exchanges_canceled, $exchange_sent[0]);
    }

    return $exchanges_canceled;
  }

  function getStudentInfosListFromSearch($search) {
    $query = $GLOBALS['bdd']->prepare('SELECT login, semestre, mail, prenom, nom FROM students WHERE lower(login) LIKE lower(CONCAT("%", ?, "%")) OR lower(CONCAT(prenom, "_", nom, "_", prenom)) LIKE lower(CONCAT("%", ?, "%")) ORDER BY nom, prenom, login');
    $GLOBALS['bdd']->execute($query, array($search, $search));

    return $query->fetchAll();
  }

  function getUVInfosListFromSearch($search) { // Plus rapide pour la recherche (et puis chaque UV est unique dans couleurs)
    $query = $GLOBALS['bdd']->prepare('SELECT uv FROM couleurs WHERE lower(uv) LIKE lower(CONCAT("%", ?, "%"))');
    $GLOBALS['bdd']->execute($query, array($search));

    return $query->fetchAll();
  }

  function getStudentInfosFromIdUV($idUV, $desinscrit = NULL, $actuel = 1) {
    $query = $GLOBALS['bdd']->prepare('SELECT students.login, students.semester, students.email, students.firstname, students.surname, students.status, uvs_followed.stat, cours.echange FROM students, cours WHERE cours.id = ? AND cours.actuel = ? AND (? IS NULL OR desinscrit = ?) AND students.login = cours.login ORDER BY nom, prenom, login');
    $GLOBALS['bdd']->execute($query, array($idUV, $actuel, $desinscrit, $desinscrit));

    return $query->fetchAll();
  }

  function getRooms($gap, $day = NULL) {
    $tasks = array();
    $passed = array();

    if ($gap < 0)
      $query = $GLOBALS['bdd']->prepare(
        'SELECT uvs_rooms.room, uvs_rooms.type, uvs_rooms.day, uvs_rooms.begin, uvs_rooms.end, uvs_rooms.gap
        FROM uvs_rooms
        WHERE (uvs_rooms.gap >= -? OR uvs_rooms.gap >= -? + 1) AND (? IS NULL OR uvs_rooms.day = ?)
        ORDER BY uvs_rooms.day, uvs_rooms.begin, uvs_rooms.end, uvs_rooms.room');
    else
      $query = $GLOBALS['bdd']->prepare(
        'SELECT uvs_rooms.room, uvs_rooms.type, uvs_rooms.day, uvs_rooms.begin, uvs_rooms.end, uvs_rooms.gap
        FROM uvs_rooms
        WHERE (uvs_rooms.gap = ? OR uvs_rooms.gap = ? + 1) AND (? IS NULL OR uvs_rooms.day = ?)
        ORDER BY uvs_rooms.day, uvs_rooms.begin, uvs_rooms.end, uvs_rooms.room');

    $GLOBALS['bdd']->execute($query, array($gap, $gap, $day, $day));
    $rooms = $query->fetchAll();

    foreach ($rooms as $room) {
      $toTest = array($room['day'], $room['begin'], $room['end']);
      $where = array_keys($passed, $toTest);

      if ($where == array()) {
        array_push($tasks, array(
          'subject' => 1,
          'begin' => $room['begin'],
          'end' => $room['end'],
          'description' => '~'.$room['gap'].'h',
          'location' => array($room['type'] => array($room['room']))));
        array_push($passed, $toTest);
      }
      else {
        $tasks[$where[0]]['subject']++;

        if (!isset($tasks[$where[0]]['location'][$room['type']]))
          $tasks[$where[0]]['location'][$room['type']] = array();
        array_push($tasks[$where[0]]['location'][$room['type']], $room['room']);
      }
    }

    return $tasks;
  }

  function getDays($startingDay, $nbrOfDays = 7) {
    $days = array();
    $date = new DateTime($startingDay);

    for ($i = 0; $i < $nbrOfDays; $i++) {
      if (!isAGoodDate($date->format('Y-m-d'))) {
        $date->modify('+1 day');
        continue;
      }

      $query = $GLOBALS['bdd']->prepare('SELECT * FROM uvs_days WHERE begin <= ? ORDER BY begin DESC LIMIT 1');
      $GLOBALS['bdd']->execute($query, array($date->format('Y-m-d')));

      $day = $query->fetch();
      $day['date'] = $date->format('Y-m-d');

      array_push($days, $day);
      $date->modify('+1 day');
    }

    return $days;
  }

  function getNextCours($login, $day) {
    $inDays = 0;
    $date = new DateTime($day);

    while (TRUE) {
      if (!isAGoodDate($date->format('Y-m-d')))
        return array('error' => 'plus cours');

      $days = getDays($date->format('Y-m-d'), 1);
      $today = $days[0];
      $allEdt = getEdtEtu($login, 1, NULL, $today['day']);

      if (count($allEdt) != 0) {
        foreach ($allEdt as $edt) {
          if (($edt['type'] == 'D' && $today['td']) || ($edt['type'] == 'T' && $today['tp']) || ($edt['type'] == 'C' && $today['cours'])) {
            $edt['inDays'] = $inDays;
            return $edt;
          }
        }
      }

      $inDays++;
      $date->modify('+1 day');
    }
  }

  function getUVsFollowed($login, $enabled = 1, $exchanged = NULL, $day = NULL) {
    $query = $GLOBALS['bdd']->prepare(
      'SELECT uvs_followed.id, uvs_followed.idUV, uvs.uv, uvs.type, uvs.groupe, uvs.day, uvs.begin, uvs.end, uvs.room, uvs.frequency, uvs.week, uvs.nbrEtu, uvs_followed.color, uvs_colors.color AS colorUV
      FROM uvs, uvs_followed, uvs_colors
      WHERE uvs_followed.login = ? AND uvs_followed.enabled = ? AND (? IS NULL OR uvs_followed.exchanged = ?) AND (? IS NULL OR uvs.day = ?) AND uvs.uv = uvs_colors.uv AND uvs.id = uvs_followed.idUV
      ORDER BY uvs.day, uvs.begin, week, groupe'
    );
    $GLOBALS['bdd']->execute($query, array($login, $enabled, $exchanged, $exchanged, $day, $day));

    return $query->fetchAll();
  }

  function getUV($uv, $type = NULL, $day = NULL) {
    $query = $GLOBALS['bdd']->prepare(
      'SELECT uvs.id, uvs.uv, type, groupe, day, begin, end, room, frequency, week, nbrEtu, color
      FROM uvs, uvs_colors
      WHERE uvs.uv = uvs_colors.uv AND uvs.uv = ? AND (? IS NULL OR type = ?) AND (? IS NULL OR day = ?)
      ORDER BY uv, day, begin, week, groupe');
    $GLOBALS['bdd']->execute($query, array($uv, $type, $type, $day, $day));

    return $query->fetchAll();
  }

  function getUVInfosFromIdUV($idUV) {
    $query = $GLOBALS['bdd']->prepare('SELECT uv, type, day, begin, end, room, groupe, frequency, week, nbrEtu FROM uvs WHERE uvs.id = ?');
    $GLOBALS['bdd']->execute($query, array($idUV));

    return $query->fetch();
  }

  function isEdtEtuVoid($login, $enabled = 1, $exchanged = NULL) {
    return getEdtEtu($login, $enabled, $exchanged) == array();
  }

  function isAnUV($uv) { // Ici on utilise couleurs pour accélérer la recherche
    $query = $GLOBALS['bdd']->prepare('SELECT uv FROM uvs_colors WHERE uv = ?');
    $GLOBALS['bdd']->execute($query, array($uv));

    return $query->rowCount() == 1;
  }

  function isAStudent($login) {
    $query = $GLOBALS['bdd']->prepare('SELECT login FROM students WHERE login = ?');
    $GLOBALS['bdd']->execute($query, array($login));

    return $query->rowCount() == 1;
  }

  function cancelIdExchange($idExchange, $login = NULL) {
    $query = $GLOBALS['bdd']->prepare('DELETE FROM envoies WHERE idEchange = ? AND login = ?');
    $GLOBALS['bdd']->execute($query, array($idExchange, $login == NULL ? $_SESSION['login'] : $login));

    // Si on était le seul à demander, on désactive l'annonce
    if (count(getEnvoiesList(NULL, $idExchange, 1)) == 0) {
      $query = $GLOBALS['bdd']->prepare('UPDATE echanges SET active = 0 WHERE idEchange = ?');
      $GLOBALS['bdd']->execute($query, array($idExchange));
    }
  }

  function refuseIdExchange($idExchange) {
    $query = $GLOBALS['bdd']->prepare('UPDATE recues SET disponible = 0, date = NOW() WHERE login = ? AND idEchange = ?');
    $GLOBALS['bdd']->execute($query, array($_SESSION['login'], $idExchange));

    if (count(getRecuesList(NULL, $idExchange, 1)) == 0) { // On regarde s'il reste encore des propositions non répondus
      // On annonce que personne n'a accepté la proposition
      $query = $GLOBALS['bdd']->prepare('UPDATE echanges SET active = 0 WHERE idEchange = ?');
      $GLOBALS['bdd']->execute($query, array($idExchange));
      // On indique à tous les demandeurs que tout le monde a refusé
      $query = $GLOBALS['bdd']->prepare('UPDATE envoies SET disponible = 0, date = NOW() WHERE idEchange = ? AND disponible = 1');
      $GLOBALS['bdd']->execute($query, array($idExchange));

      $envoies = getEnvoiesList(NULL, $idExchange, 1);
      foreach ($envoies as $envoie) {
        $infosLogin = getStudentInfos($envoie['login']);
        mail($infosLogin['login'], 'Echange refusé', 'Salut !'.PHP_EOL.'Une demande d\'échange a été refusée par tout le monde.'.PHP_EOL.'Tente ta chance avec une autre proposition!', 'From: agendutc@nastuzzi.fr');
      }
    }
  }

  function isAGoodDate($week) {
    $query = $GLOBALS['bdd']->prepare('SELECT * FROM uvs_days WHERE begin <= ? AND end >= ? LIMIT 1');
    $GLOBALS['bdd']->execute($query, array($week, $week));

    return $query->rowCount() == 1;
  }

  if (isUpdating()) {
    echo 'Emploi d\'UTemps est en cours de mise à jour, veuillez patienter. La page se relancera d\'elle-même lorsque la mise à jour sera terminée
    <script>
    setTimeout(function(){ window.reload(); }, 5000);
    </script>';
    exit;
  }

  if (isset($_GET['week']) && isAGoodDate($_GET['week']))
    $_SESSION['week'] = date('Y-m-d', strtotime('monday this week', strtotime($_GET['week'])));
  elseif (isset($_GET['mode']) && $_GET['mode'] == 'organiser' && isset($_GET['week']))
    $_SESSION['week'] = date('Y-m-d', strtotime('monday this week'));
?>
