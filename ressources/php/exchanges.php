<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/include.php');
  include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/functions/exchanges.php');

  header('Content-Type: application/json');

//returnJSON(array('error' => 'Echanges bloqués maximum 1h, écoute l\'amphi'));

  if (isset($_GET['mode']) && is_string($_GET['mode']))
    $mode = $_GET['mode'];
  else
    $mode = 'get';

  $result = FALSE;
  if ($mode == 'get' && isset($_GET['idUV']) && is_string($_GET['idUV']) && isset($_GET['idUV2']) && is_string($_GET['idUV2'])) {
    $exchanges = getExchanges(NULL, $_GET['idUV'], $_GET['idUV2']);

    returnJSON(array(
      'uv' => getUVInfosFromIdUV($_GET['idUV']),
      'uv2' => getUVInfosFromIdUV($_GET['idUV2']),
      'sent' => ((count($exchanges) == 0) ? array() : getSentExchanges(NULL, NULL, $exchanges[0]['id'])),
      'received' => ((count($exchanges) == 0) ? array() : getReceivedExchanges(NULL, NULL, $exchanges[0]['id']))
    ));
  }
  if ($mode == 'ask' && isset($_GET['idUV']) && is_string($_GET['idUV']) && isset($_GET['idUV2']) && is_string($_GET['idUV2']) && isset($_GET['note']) && is_string($_GET['note']))
    $result = askExchange($_SESSION['login'], $_GET['idUV'], $_GET['idUV2'], $_GET['note']);
  elseif ($mode == 'cancelAsk' && isset($_GET['idExchange']) && is_string($_GET['idExchange']))
    $result = cancelAskExchange($_GET['idExchange']);
  elseif ($mode == 'askCancel' && isset($_GET['idExchange']) && is_string($_GET['idExchange']) && isset($_GET['note']) && is_string($_GET['note']))
    $result = askCancelExchange($_GET['idExchange'], $_GET['note']);
  elseif ($mode == 'cancelAskCancel' && isset($_GET['idExchange']) && is_string($_GET['idExchange']))
    $result = cancelAskCancelExchange($_GET['idExchange']);
  elseif ($mode == 'cancel' && isset($_GET['idExchange']) && is_string($_GET['idExchange']))
    $result = cancelExchange($_GET['idExchange']);
  elseif ($mode == 'accept' && isset($_GET['idExchange']) && is_string($_GET['idExchange']))
    $result = acceptReceivedExchange($_GET['idExchange']);
  elseif ($mode == 'refuse' && isset($_GET['idExchange']) && is_string($_GET['idExchange']))
    $result = refuseReceivedExchange($_GET['idExchange']);
  elseif ($mode == 'cancelSentAll') {
    $sentExchanges = getSentExchanges($_SESSION['login'], NULL, NULL, 1, 0);

    $result = TRUE;
    foreach ($sentExchanges as $sentExchange)
      $result = cancelSentExchange($sentExchange['idExchange']);

    $nbr = count($sentExchanges);
  }
  elseif ($mode == 'refuseAll') {
    $receivedExchanges = getReceivedExchanges($_SESSION['login'], NULL, NULL, 1, 0);

    $result = TRUE;
    foreach ($receivedExchanges as $receivedExchange)
      $result = refuseReceivedExchange($receivedExchange['idExchange']);

    $nbr = count($receivedExchanges);
  }

  if ($result === TRUE) {
    if ($mode == 'ask')
      returnJSON(array('success' => 'Proposition d\'échange réalisée avec succès. Un mail de confirmation t\'a été envoyé'));
    elseif ($mode == 'cancelAsk')
      returnJSON(array('success' => 'Proposition d\'échange annulée avec succès. Un mail de confirmation t\'a été envoyé'));
    elseif ($mode == 'askCancel')
      returnJSON(array('success' => 'Demande d\'annulation de l\'échange réalisée avec succès. Un mail de confirmation t\'a été envoyé'));
    elseif ($mode == 'cancelAskCancel')
      returnJSON(array('success' => 'Demande d\'annulation annulée avec succès. Tu ne pourras plus demander l\'annulation de cet échange. Un mail de confirmation t\'a été envoyé'));
    elseif ($mode == 'cancel')
      returnJSON(array('success' => 'Echange annulé avec succès. Les emplois du temps ont été mis à jour et la proposition d\'échange refusée en conséquence. Un mail de confirmation t\'a été envoyé'));
    elseif ($mode == 'accept')
      returnJSON(array('success' => 'Proposition acceptée avec succès. Les emplois du temps ont été mis à jour en conséquence. Un mail de confirmation t\'a été envoyé'));
    elseif ($mode == 'refuse')
      returnJSON(array('success' => 'Proposition refusée avec succès. Un mail de confirmation t\'a été envoyé'));
    elseif ($mode == 'cancelSentAll') {
      if ($nbr == 0)
        returnJSON(array('info' => 'Aucune proposition annulée'));
      elseif ($nbr == 1)
        returnJSON(array('success' => '1 seule proposition envoyée annulée avec succès. Un mail de confirmation t\'a été envoyé'));
      else
        returnJSON(array('success' => $nbr. ' propositions envoyées annulées avec succès. Un mail de confirmation t\'a été envoyé pour chaque proposition envoyée annulée'));
    }
  elseif ($mode == 'refuseAll') {
    if ($nbr == 0)
      returnJSON(array('info' => 'Aucune proposition refusée'));
    elseif ($nbr == 1)
      returnJSON(array('success' => '1 seule proposition refusée avec succès. Un mail de confirmation t\'a été envoyé'));
    else
      returnJSON(array('success' => $nbr. ' propositions refusées avec succès. Un mail de confirmation t\'a été envoyé pour chaque proposition refusée'));
  }
  }
  elseif ($result === FALSE)
    returnJSON(array('error' => 'Non valide'));
  else
    returnJSON(array('error' => $result));
?>
