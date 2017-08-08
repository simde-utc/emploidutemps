<?php
  date_default_timezone_set('Europe/Paris');
  mb_internal_encoding("UTF-8");
  session_start();

  ini_set('display_errors', 1);  ini_set('display_startup_errors', 1);  error_reporting(E_ALL);

  $etuPic = '<i class="searchImg fa fa-4x fa-user-o" style="padding-left: 1px; padding-top: 3px;" aria-hidden="true"></i>';
  $uvPic = '<i class="searchImg fa fa-4x fa-graduation-cap" style="margin-left:10%;" aria-hidden="true"></i>';
  $colors = array('#7DC779', '#82A1CA', '#F2D41F', '#457293', '#AB7AC6', '#DF6F53', '#B0CEE9', '#AAAAAA', '#1C704E');
  $jours = array('lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche');
  include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/class/db.php');
  include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/class/curl.php');
  include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/class/cas.php');

  if (isset($_GET['MODCASID']) && is_string($_GET['MODCASID']))
    $_SESSION['MODCASID'] = $_GET['MODCASID'];

  $db = new db();

  function shutdown() {
    $GLOBALS['db'] = null;
  }
  register_shutdown_function('shutdown');

  function getStudentInfos($login = NULL) {
    $query = $GLOBALS['db']->request(
      'SELECT * FROM students WHERE (? IS NULL OR login = ?)',
      array($login, $login)
    );

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
      $_SESSION['admin'] = FALSE;
  		$_SESSION['ticket'] = $_GET['ticket'];
      $infos = getStudentInfos($_SESSION['login']);
      $_SESSION['uvs'] = $infos['uvs'];

      if (isset($_GET['week']) && is_string($_GET['week']) && isAGoodDate($_GET['week']))
        $_SESSION['week'] = $_GET['week'];
      elseif (isAGoodDate(date('Y-m-d', strtotime('monday this week'))))
        $_SESSION['week'] = date('Y-m-d', strtotime('monday this week'));
      else {
        $query = $GLOBALS['db']->request(
          'SELECT * FROM uvs_days
            ORDER BY uvs_days.end DESC
            LIMIT 1',
          array()
        );

        $data = $query->fetch();
        $_SESSION['week'] = $data['end'];
      }

      $query = $GLOBALS['db']->request(
        'INSERT INTO debug(login) VALUES(?)',
        array($_SESSION['login'])
      );

      include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/functions/groups.php');
      setGroups();

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
    $query = $GLOBALS['db']->request(
      'SELECT status FROM students WHERE email = ?',
      array($mail)
    );
    $data = $query->fetch();
/*
    if ($data['status'] != '-1')
      return mail($mail, $subject, $message.PHP_EOL.PHP_EOL.'Pour arrêter de recevoir des mails du service, tu peux à tout moment te désinscrire en cliquant ici: https://assos.utc.fr/emploidutemps/?param=sedesinscrire'.PHP_EOL.PHP_EOL.'En cas d\'erreur ou de bug, contacte-nous à cette adresse: simde@assos.utc.fr'.PHP_EOL.PHP_EOL.'Il y a une vie après les cours,'.PHP_EOL.'Le SiMDE', 'FROM:'.$from);
*/
    return FALSE;
  }

  function isUpdating() {
    return $_SERVER['SCRIPT_NAME'] != '/emploidutemps/ressources/php/maj.php' && (file_exists($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/logs/'.'update') || file_exists($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/logs/'.'login'));
  }

  function getARandomColor() {
    return $GLOBALS['colors'][mt_rand(1, count($GLOBALS['colors'])) - 1];
  }

  function getRooms($gap, $day = NULL) {
    $tasks = array();
    $passed = array();

    if ($gap < 0)
      $query = $GLOBALS['db']->prepare(
        'SELECT uvs_rooms.room, uvs_rooms.type, uvs_rooms.day, uvs_rooms.begin, uvs_rooms.end, uvs_rooms.gap
        FROM uvs_rooms
        WHERE (uvs_rooms.gap >= -? OR uvs_rooms.gap >= -? + 1) AND (? IS NULL OR uvs_rooms.day = ?)
        ORDER BY uvs_rooms.day, uvs_rooms.begin, uvs_rooms.end, uvs_rooms.room'
      );
    else
      $query = $GLOBALS['db']->prepare(
        'SELECT uvs_rooms.room, uvs_rooms.type, uvs_rooms.day, uvs_rooms.begin, uvs_rooms.end, uvs_rooms.gap
        FROM uvs_rooms
        WHERE (uvs_rooms.gap = ? OR uvs_rooms.gap = ? + 1) AND (? IS NULL OR uvs_rooms.day = ?)
        ORDER BY uvs_rooms.day, uvs_rooms.begin, uvs_rooms.end, uvs_rooms.room'
      );

    $GLOBALS['db']->execute($query, array($gap, $gap, $day, $day));
    $rooms = $query->fetchAll();

    foreach ($rooms as $room) {
      $toTest = array($room['day'], $room['begin'], $room['end']);
      $where = array_keys($passed, $toTest);

      if ($where == array()) {
        array_push($tasks, array(
          'subject' => 1,
          'day' => $room['day'],
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

      $query = $GLOBALS['db']->request(
        'SELECT * FROM uvs_days WHERE begin <= ? ORDER BY begin DESC LIMIT 1',
        array($date->format('Y-m-d'))
      );

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
      $allEdt = getUVsFollowed($login, 1, NULL, $today['day']);

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
    $query = $GLOBALS['db']->request(
      'SELECT uvs_followed.id, uvs_followed.idUV, uvs.uv, uvs.type, uvs.groupe, uvs.day, uvs.begin, uvs.end, uvs.room, uvs.frequency, uvs.week, uvs.nbrEtu, uvs_followed.color, uvs_colors.color AS uvColor
        FROM uvs, uvs_followed, uvs_colors
        WHERE uvs_followed.login = ? AND uvs_followed.enabled = ? AND (? IS NULL OR uvs_followed.exchanged = ?) AND (? IS NULL OR uvs.day = ?) AND uvs.uv = uvs_colors.uv AND uvs.id = uvs_followed.idUV
        ORDER BY uvs.day, uvs.begin, week, groupe',
      array($login, $enabled, $exchanged, $exchanged, $day, $day)
    );

    return $query->fetchAll();
  }

  function getUV($uv, $type = NULL, $day = NULL) {
    $query = $GLOBALS['db']->request(
      'SELECT uvs.id, uvs.uv, type, groupe, day, begin, end, room, frequency, week, nbrEtu, color
        FROM uvs, uvs_colors
        WHERE uvs.uv = uvs_colors.uv AND uvs.uv = ? AND (? IS NULL OR type = ?) AND (? IS NULL OR day = ?)
        ORDER BY uv, day, begin, week, groupe',
      array($uv, $type, $type, $day, $day)
    );

    return $query->fetchAll();
  }

  function getUVInfosFromIdUV($idUV) {
    $query = $GLOBALS['db']->request(
      'SELECT uv, type, day, begin, end, room, groupe, frequency, week, nbrEtu
        FROM uvs
        WHERE uvs.id = ?',
      array($idUV)
    );

    return $query->fetch();
  }

  function isEdtEtuVoid($login, $enabled = 1, $exchanged = NULL) {
    return getUVsFollowed($login, $enabled, $exchanged) == array();
  }

  function isAnUV($uv) { // Ici on utilise couleurs pour accélérer la recherche
    $query = $GLOBALS['db']->request(
      'SELECT uv
        FROM uvs_colors
        WHERE uv = ?',
      array($uv)
    );

    return $query->rowCount() == 1;
  }

  function isAStudent($login) {
    $query = $GLOBALS['db']->request(
      'SELECT login
        FROM students
        WHERE login = ?',
      array($login)
    );

    return $query->rowCount() == 1;
  }

  function isAGoodDate($week) {
    $query = $GLOBALS['db']->request(
      'SELECT * FROM uvs_days
        WHERE begin <= ? AND end >= ?
        LIMIT 1',
      array($week, $week)
    );

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
  /*elseif (isset($_GET['mode']) && $_GET['mode'] == 'organiser' && isset($_GET['week']))
    $_SESSION['week'] = date('Y-m-d', strtotime('monday this week'));*/
?>
