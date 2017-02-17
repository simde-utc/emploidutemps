<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/include.php');

  function createEvent($jourStart, $heureStart, $jourEnd, $heureEnd, $summary, $description, $location) {
    echo '
    BEGIN:VEVENT<br />
    DTSTAMP:'.$GLOBALS['date'].'<br />
    UID:'.$GLOBALS['date'].'-'.$GLOBALS['id']++.'-'.$_SESSION['login'].'@nastuzzi.fr<br />
    DTSTART;TZID="Europe/Berlin":'.$jourStart.'T'.$heureStart.'<br />
    DTEND;TZID="Europe/Berlin":'.$jourEnd.'T'.$heureEnd.'<br />
    SUMMARY:'.$summary.'<br />', ($description != NULL ? '
    DESCRIPTION:'.$description.'<br />' : ''), ($location != NULL ? '
    LOCATION:'.$location.'<br />' : ''), ($GLOBALS['alarm'] != -1 ? '
    BEGIN:VALARM<br />
    ACTION:DISPLAY<br />
    DESCRIPTION:'.$summary.($description != NULL ? ' - '.$description : '').'<br />
    TRIGGER:-PT'.$GLOBALS['alarm'].'M<br />
    END:VALARM<br />' : ''),'
    END:VEVENT<br />';
  }

  echo 'BEGIN:VCALENDAR<br />
  PRODID: Emploi d\'UTemps - UTC - SIMDE/BDE<br />
  VERSION:2.0<br />
  CALSCALE:GREGORIAN<br />
  METHOD:PUBLISH<br />
  X-WR-CALNAME: ', $_SESSION['mail'], '<br />
  X-WR-TIMEZONE:Europe/Paris<br />
  BEGIN:VTIMEZONE<br />
  TZID:Europe/Paris<br />
  X-LIC-LOCATION:Europe/Paris<br />
  BEGIN:DAYLIGHT<br />
  TZOFFSETFROM:+0100<br />
  TZOFFSETTO:+0200<br />
  TZNAME:CEST<br />
  DTSTART:19700329T020000<br />
  RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU<br />
  END:DAYLIGHT<br />
  BEGIN:STANDARD<br />
  TZOFFSETFROM:+0200<br />
  TZOFFSETTO:+0100<br />
  TZNAME:CET<br />
  DTSTART:19701025T030000<br />
  RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU<br />
  END:STANDARD<br />
  END:VTIMEZONE<br />';

  $edt = getEdtEtu($_SESSION['login']);

  $date = date('Ymd').'T'.date('His').'Z';
  $id = 0;

  $alarm = -1; // Désactiver l'alarme
  $alarm = 30;

  $jours = $GLOBALS['bdd']->prepare('SELECT * FROM jours ORDER BY jour');
  $GLOBALS['bdd']->execute($jours, array());

  foreach ($jours->fetchAll() as $jour) {
    $type = $jour['type'];
    $jourStart = str_replace('-', '', $jour['jour']);
    $jourEnd = $jourStart;
    $description = $jour['infos'];
    $cours = TRUE;
    $td = TRUE;
    $tp = TRUE;
/*
    if ($type < 0 || $type == 50 || ($type > 6 && $type < 10))
      echo 'Mauvais type de jour: '.$jour['jour'];
*/
    if ($type < 50) {
      if ($type < 40) {
        if ($type < 30) {
          if ($type < 20) {
            if ($type >= 10) {
              $type -= 10;
              $cours = FALSE;
            }
          }
          else {
            $type -= 20;
            $cours = FALSE;
            $td = FALSE;
          }
        }
        else {
          $type -= 30;
          $cours = FALSE;
          $tp = FALSE;
        }
      }
      else {
        $type -= 40;
        $td = FALSE;
        $tp = FALSE;
      }

      foreach ($edt as $uv) {
        if ($uv['jour'] < $type)
          continue;
        elseif ($uv['jour'] > $type)
          break;

        if (($uv['type'] == 'C' && !$cours) || ($uv['type'] == 'D' && !$td) || ($uv['type'] == 'T' && !$tp))
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

        createEvent($jourStart, $debut, $jourEnd, $fin, $summary, $description, $uv['salle']);
      }

      // Afficher pour chaque TPs et TDs aussi
      //$summary = $type.$uv;
    }
    else {
      $type -= 50;
      $cours = FALSE;
      $td = FALSE;
      $tp = FALSE;

      $heureStart = '000000';
      $heureEnd = '000000';

      $temp = date_create($jourEnd);
      date_add($temp, date_interval_create_from_date_string($type.' days'));
      $jourEnd = date_format($temp, 'Ymd');

      $split = explode(' - ', $jour['infos']);
      $summary = (isset($split[0]) ? $split[0] : $jour['infos']);
      $description = (isset($split[1]) ? $split[1] : NULL);
      $location = (isset($split[2]) ? $split[2] : NULL);

      createEvent($jourStart, $heureStart, $jourEnd, $heureEnd, $summary, $description, $location);
    }
  }

  echo 'END:VCALENDAR';

?>
