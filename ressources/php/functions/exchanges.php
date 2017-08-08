<?php
  function getExchangesReceived($login = NULL, $id = NULL, $idExchange = NULL, $available = NULL, $exchanged = NULL, $idUV = NULL, $idUV2 = NULL, $date = NULL, $idSent = NULL) {
    $query = $GLOBALS['db']->request(
      'SELECT exchanges_received.id, idExchange, login, exchanges.idUV, exchanges.idUV2, exchanges_received.date, exchanges_received.available, exchanges_received.exchanged, exchanges.enabled, idSent
        FROM exchanges_received, exchanges
        WHERE (? IS NULL OR login = ?) AND (? IS NULL OR exchanges_received.id = ?) AND (? IS NULL OR exchanges.idUV = ?) AND (? IS NULL OR exchanges.idUV2 = ?) AND (? IS NULL OR idExchange = ?)
        AND (? IS NULL OR exchanges_received.available = ?) AND (? IS NULL OR exchanges_received.exchanged = ?) AND (? IS NULL OR exchanges_received.date = ?) AND (? IS NULL OR idSent = ?)
        AND exchanges.id = exchanges_received.idExchange',
      array($login, $login, $id, $id, $idUV, $idUV, $idUV2, $idUV2, $idExchange, $idExchange, $available, $available, $exchanged, $exchanged, $date, $date, $idSent, $idSent)
    );

    return $query->fetchAll();
  }

  function getExchanges($idUV, $idUV2, $enabled = NULL) {
    $query = $GLOBALS['db']->request(
      'SELECT id, enabled
        FROM exchanges
        WHERE idUV = ? AND idUV2 = ? AND (? IS NULL OR enabled = ?)',
      array($idUV, $idUV2, $enabled, $enabled)
    );

    return $query->fetchAll();
  }

  function getExchangesSent($login = NULL, $id = NULL, $idExchange = NULL, $available = NULL, $exchanged = NULL, $idUV = NULL, $idUV2 = NULL, $date = NULL, $idReceived = NULL) {
    $query = $GLOBALS['db']->request(
      'SELECT exchanges_sent.id, idExchange, login, exchanges.idUV, exchanges.idUV2, exchanges_sent.date, exchanges_sent.note, exchanges_sent.available, exchanges_sent.exchanged, idReceived
        FROM exchanges_sent, exchanges
        WHERE (? IS NULL OR login = ?) AND (? IS NULL OR exchanges_sent.id = ?) AND (? IS NULL OR exchanges.idUV = ?) AND (? IS NULL OR exchanges.idUV2 = ?) AND (? IS NULL OR idExchange = ?)
        AND (? IS NULL OR exchanges_sent.available = ?) AND (? IS NULL OR exchanges_sent.exchanged = ?) AND (? IS NULL OR exchanges_sent.date = ?) AND (? IS NULL OR idReceived = ?)
        AND exchanges.id = exchanges_sent.idExchange
        ORDER BY date',
      array($login, $login, $id, $id, $idUV, $idUV, $idUV2, $idUV2, $idExchange, $idExchange, $available, $available, $exchanged, $exchanged, $date, $date, $idReceived, $idReceived)
    );

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

  /*
  function cancelIdExchange($idExchange, $login = NULL) {
    $query = $GLOBALS['db']->prepare('DELETE FROM envoies WHERE idEchange = ? AND login = ?');
    $GLOBALS['db']->execute($query, array($idExchange, $login == NULL ? $_SESSION['login'] : $login));

    // Si on était le seul à demander, on désactive l'annonce
    if (count(getEnvoiesList(NULL, $idExchange, 1)) == 0) {
      $query = $GLOBALS['db']->prepare('UPDATE echanges SET active = 0 WHERE idEchange = ?');
      $GLOBALS['db']->execute($query, array($idExchange));
    }
  }

  function refuseIdExchange($idExchange) {
    $query = $GLOBALS['db']->prepare('UPDATE recues SET disponible = 0, date = NOW() WHERE login = ? AND idEchange = ?');
    $GLOBALS['db']->execute($query, array($_SESSION['login'], $idExchange));

    if (count(getRecuesList(NULL, $idExchange, 1)) == 0) { // On regarde s'il reste encore des propositions non répondus
      // On annonce que personne n'a accepté la proposition
      $query = $GLOBALS['db']->prepare('UPDATE echanges SET active = 0 WHERE idEchange = ?');
      $GLOBALS['db']->execute($query, array($idExchange));
      // On indique à tous les demandeurs que tout le monde a refusé
      $query = $GLOBALS['db']->prepare('UPDATE envoies SET disponible = 0, date = NOW() WHERE idEchange = ? AND disponible = 1');
      $GLOBALS['db']->execute($query, array($idExchange));

      $envoies = getEnvoiesList(NULL, $idExchange, 1);
      foreach ($envoies as $envoie) {
        $infosLogin = getStudentInfos($envoie['login']);
        mail($infosLogin['login'], 'Echange refusé', 'Salut !'.PHP_EOL.'Une demande d\'échange a été refusée par tout le monde.'.PHP_EOL.'Tente ta chance avec une autre proposition!', 'From: agendutc@nastuzzi.fr');
      }
    }
  }
  */
