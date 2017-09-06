<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps'.'/ressources/php/include.php');
  include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps'.'/ressources/php/functions/events.php');

  header('Content-Type: application/json');

  if (isGetSet(array('mode', 'idEvent')) && $_GET['mode'] == 'del') {
    $events = getEvents(NULL, $_GET['idEvent'], $_SESSION['login']);

    if (count($events) == 0) {
      delEvent($_GET['idEvent']);
      returnJSON(array('success' => 'Evènement supprimé avec succès'));
    }
    else {
      delEvent($_GET['idEvent']);
      returnJSON(array('success' => 'Evènement supprimé avec succès'.($events[0]['type'] != 'event' && $events[0]['type'] != 'meeting' ? '. Comme tu es celui/celle qui a initié l\'évènement, celui-ci a été supprimé des emploi du temps des autres étudiants' : '')));
    }
  }

  if (!isGetSet(array('mode', 'date', 'begin', 'end', 'subject', 'type', 'sendMail')) || !isset($_GET['description']) || !is_string($_GET['description']) || !isset($_GET['location']) || !is_string($_GET['location']))
    returnJSON(array('error' => 'Arguments manquants'));

  if (empty($_GET['description']))
    $_GET['description'] = NULL;
  if (empty($_GET['location']))
    $_GET['location'] = NULL;

  if ($_GET['mode'] == 'add') {
    if (!isAGoodDate($_GET['date']))
      returnJSON(array('error' => 'Impossible de créer un évènement en dehors calendrier universitaire'));

    if ($_GET['type'] == 'event' || $_GET['type'] == 'meeting') {
      if (count(getEvents(NULL, NULL, $_SESSION['login'], NULL, $_SESSION['login'], $_GET['type'], $_GET['date'], $_GET['begin'], $_GET['end'], $_GET['subject'])) != 0)
      returnJSON(array('error' => ($_GET['type'] == 'event' ? 'Evènement déjà créé' : 'Réunion déjà créée').' au même moment avec le même sujet'));

      $id = inviteEvent(createEvent($_SESSION['login'], NULL, $_GET['type'], $_GET['date'], $_GET['begin'], $_GET['end'], $_GET['subject'], $_GET['description'], $_GET['location']), $_SESSION['login']);

      if ($_GET['sendMail'] == 'true') {
        sendMail($_SESSION['email'],
        'Création d\'un évènement',
        'Tu viens de te créer '.($_GET['type'] == 'event' ? 'un évènement' : 'une réunion').' qui a pour sujet: '.$_GET['subject'].'

        Tu peux récupérer ton évènement dans ton calendrier Google ou Apple en cliquant ici: https://'.$_SERVER['HTTP_HOST'].'/emploidutemps/ressources/php/exports.php?mode=event&idEventFollowed='.$id
        );
      }

      returnJSON(array('success' => ($_GET['type'] == 'event' ? 'Evènement créé' : 'Réunion créée').' avec succès. Tu peux sauvegarder ton évènement dans ton calendrier Google ou Apple en cliquant ici: <a href="/emploidutemps/ressources/php/exports.php?mode=event&idEventFollowed='.$id.'">Télécharger l\'évènement au format .ics</a>'));
    }
  }
  elseif ($_GET['mode'] == 'edit' && isGetSet(array('idEvent'))) {
    if ($_GET['type'] == 'event' || $_GET['type'] == 'meeting') {
      if (count(getEvents(NULL, $_GET['idEvent'], $_SESSION['login'])) == 0)
        returnJSON(array('error' => 'Tu ne peux pas modifier cet évènement'));

      editEvent($_GET['idEvent'], $_GET['date'], $_GET['begin'], $_GET['end'], $_GET['subject'], $_GET['description'], $_GET['location']);
      sendMail($_SESSION['email'],
      'Modification d\'un évènement',
      'Tu viens de te modifier '.($_GET['type'] == 'event' ? 'un évènement' : 'une réunion').' qui a maintenant pour sujet: '.$_GET['subject'].'

      Tu peux récupérer ton évènement dans ton calendrier Google ou Apple en cliquant ici: https://'.$_SERVER['HTTP_HOST'].'/emploidutemps/ressources/php/exports.php?mode=event&idEventFollowed='.$_GET['idEvent']
      );

      returnJSON(array('success' => ($_GET['type'] == 'event' ? 'Evènement modifié' : 'Réunion modifiée').' avec succès. Tu peux sauvegarder ton évènement dans ton calendrier Google ou Apple en cliquant ici: <a href="/emploidutemps/ressources/php/exports.php?mode=event&idEventFollowed='.$_GET['idEvent'].'">Télécharger l\'évènement au format .ics</a>'));
    }
  }

  returnJSON(array('error' => 'Mauvais mode'));
?>
