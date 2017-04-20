<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps'.'/ressources/php/include.php');

  //mkdir($_SERVER['DOCUMENT_ROOT'].'/emploidutemps'.'/ical/');
  $file = $_SERVER['DOCUMENT_ROOT'].'/emploidutemps'.'/ical/'.$_SESSION['login'].'.ics';

  function createEvent($jourStart, $heureStart, $jourEnd, $heureEnd, $summary, $description, $location, $alarm) {
    if ($heureStart != '')
      $jourStart .= 'T'.$heureStart;

    if ($heureEnd != '')
      $jourEnd .= 'T'.$heureEnd;

    $GLOBALS['toWrite'] .= '
BEGIN:VEVENT
DTSTAMP:'.$GLOBALS['date'].'
UID:'.$GLOBALS['date'].'-'.$GLOBALS['id']++.'-'.$_SESSION['login'].'@nastuzzi.fr
DTSTART;TZID="Europe/Paris":'.$jourStart.'
DTEND;TZID="Europe/Paris":'.$jourEnd.'
SUMMARY:'.$summary.''.($description != NULL ? '
DESCRIPTION:'.$description.'' : '').($location != NULL ? '
LOCATION:'.$location.'' : '').($alarm != 0 ? '
BEGIN:VALARM
ACTION:DISPLAY
DESCRIPTION:'.$summary.($description != NULL ? ' - '.$description : '').'
TRIGGER:-PT'.$alarm.'M
END:VALARM' : '').'
END:VEVENT';
  }

  $toWrite = 'BEGIN:VCALENDAR
PRODID:Emploi d\'UTemps - UTC - SIMDE/BDE
VERSION:2.0
CALSCALE:GREGORIAN
METHOD:PUBLISH
X-WR-CALNAME:'.$_SESSION['mail'].'
X-WR-TIMEZONE:Europe/Paris
BEGIN:VTIMEZONE
TZID:Europe/Paris
X-LIC-LOCATION:Europe/Paris
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE';

  $edt = getEdtEtu($_SESSION['login']);

  $date = date('Ymd').'T'.date('His').'Z';
  $id = 0;

  $alarm = (isset($_GET['alarm']) && $_GET['alarm'] > 0 ? $_GET['alarm'] : 0); // 0 désactive l'alarme
  $begin = (isset($_GET['begin']) ? $_GET['begin'] : '0001-01-01');
  $end = (isset($_GET['end']) ? $_GET['end'] : '9999-12-31');

  $jours = $GLOBALS['bdd']->prepare('SELECT * FROM days WHERE begin BETWEEN ? AND ? ORDER BY begin, end');
  $GLOBALS['bdd']->execute($jours, array($begin, $end));

  foreach ($jours->fetchAll() as $jour) {
    $jourStart = str_replace('-', '', $jour['begin']);
    $jourEnd = str_replace('-', '', $jour['end']);
    $description = $jour['infos'];

    if ($jour['cours'] || $jour['td'] || $jour['tp']) {
      foreach ($edt as $uv) {
        if ($uv['jour'] < $jour['day'])
          continue;
        elseif ($uv['jour'] > $jour['day'])
          break;

        if (($uv['type'] == 'C' && !$jour['cours']) || ($uv['type'] == 'D' && !$jour['td']) || ($uv['type'] == 'T' && !$jour['tp']))
          continue;

        if ($jour['alternance'] != NULL && $uv['frequence'] == 2 && $jour['alternance'] != $uv['semaine'])
          continue;

        $description = $jour['infos'];

        $debut = str_replace(':', '', $uv['debut']).'00';
        $fin = str_replace(':', '', $uv['fin']).'00';

        if ($uv['type'] == 'C')
          $summary = 'Cours de '.$uv['uv'];
        elseif ($uv['type'] == 'D')
          $summary = 'TD de '.$uv['uv'];
        elseif ($uv['type'] == 'T') {
          $summary = 'TP de '.$uv['uv'];
          if ($uv['frequence'] == 3) {
            $summary .= ' (semaine '.$jour['semaine'].')';
            $description .= ($description != NULL ? '. ' : '').'TP à réaliser uniquement 1 fois toutes les 3 semaines. Consulter le planning de TPs '.$uv['uv'].' pour aller aux bons TPs';
          }
        }
        else
          continue;

        createEvent($jourStart, $debut, $jourEnd, $fin, $summary, $description, $uv['salle'], $alarm);
      }
    }
    else {
      $split = explode(' - ', $jour['infos']);
      $summary = (isset($split[0]) ? $split[0] : $jour['infos']);
      $description = (isset($split[1]) ? $split[1] : NULL);
      $location = (isset($split[2]) ? $split[2] : NULL);

      createEvent($jourStart, '', $jourEnd, '', $summary, $description, $location, 0);
    }
  }

  file_put_contents($file, $toWrite.'
END:VCALENDAR');

  echo '/emploidutemps'.'/ical/'.$_SESSION['login'].'.ics';
?>
