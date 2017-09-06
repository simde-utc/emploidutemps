<?php
  function createEvent($creator, $creator_asso, $type, $date, $begin, $end, $subject, $description, $location) {
    $GLOBALS['db']->request(
      'INSERT INTO events(creator, creator_asso, type, date, begin, end, subject, description, location) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)',
      array($creator, $creator_asso, $type, $date, $begin, $end, $subject, $description, $location)
    );

    $query = $GLOBALS['db']->request(
      'SELECT id
        FROM events
        WHERE (? IS NULL OR events.creator = ?) AND (? IS NULL OR events.creator_asso = ?) AND (? IS NULL OR events.type = ?) AND (? IS NULL OR events.date = ?) AND (? IS NULL OR events.begin = ?) AND (? IS NULL OR events.end = ?) AND (? IS NULL OR events.subject = ?)',
      array($creator, $creator, $creator_asso, $creator_asso, $type, $type, $date, $date, $begin, $begin, $end, $end, $subject, $subject)
    );

    $data = $query->fetch();
    return $data['id'];
  }

  function editEvent($idEvent, $date, $begin, $end, $subject, $description, $location) {
    $GLOBALS['db']->request(
      'UPDATE events SET date = ?, begin = ?, end = ?, subject = ?, description = ?, location = ? WHERE id = ?',
      array($date, $begin, $end, $subject, $description, $location, $idEvent)
    );
  }
  function delEvent($idEvent, $login = NULL) {
    $events = getEvents(NULL, $idEvent, NULL, NULL, $login);

    if (count($events) == 0)
      returnJSON(array('error' => 'Aucun évènement à supprimer'));

    $GLOBALS['db']->request(
      'DELETE FROM events_followed WHERE idEvent = ? AND (? IS NULL OR login = ?)',
      array($idEvent, $login, $login)
    );

    if (count($events) == 1 || $login == NULL) {
      $GLOBALS['db']->request(
        'DELETE FROM events_followed WHERE idEvent = ? AND (? IS NULL OR login = ?)',
        array($idEvent, $login, $login)
      );
    }
  }

  function inviteEvent($idEvent, $login, $color = NULL) {
    $GLOBALS['db']->request(
      'INSERT INTO events_followed(idEvent, login, color) VALUES(?, ?, ?)',
      array($idEvent, $login, $color)
    );

    $data = getEvents(NULL, $idEvent, NULL, NULL, $login);
    return $data[0]['id'];
  }
