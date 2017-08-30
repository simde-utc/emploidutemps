<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/include.php');
  include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/functions/exchanges.php');

  header('Content-Type: application/json');

  function returnJSON($array) {
    echo json_encode($array);
    exit;
  }

  if (isset($_GET['mode']) && is_string($_GET['mode']))
    $mode = $_GET['mode'];
  else
    $mode = 'get';

  $result = FALSE;
  if ($mode == 'ask' && isset($_GET['idUV']) && is_string($_GET['idUV']) && isset($_GET['idUV2']) && is_string($_GET['idUV2']) && isset($_GET['note']) && is_string($_GET['note']))
    $result = askExchange($_SESSION['login'], $_GET['idUV'], $_GET['idUV2'], $_GET['note']);
  elseif ($mode == 'cancelSent' && isset($_GET['idExchange']) && is_string($_GET['idExchange']))
    $result = cancelSentExchange($_GET['idExchange']);
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
      returnJSON(array('info' => 'Proposition d\'échange réalisée avec succès. Un mail de confirmation t\'a été envoyé'));
    elseif ($mode == 'cancelSent')
      returnJSON(array('info' => 'Proposition d\'échange annulée avec succès. Un mail de confirmation t\'a été envoyé'));
    elseif ($mode == 'askCancel')
      returnJSON(array('info' => 'Demande d\'annulation de l\'échange réalisée avec succès. Un mail de confirmation t\'a été envoyé'));
    elseif ($mode == 'cancelAskCancel')
      returnJSON(array('info' => 'Demande d\'annulation annulée avec succès. Tu ne pourras plus demander l\'annulation de cet échange. Un mail de confirmation t\'a été envoyé'));
    elseif ($mode == 'cancel')
      returnJSON(array('info' => 'Echange annulé avec succès. Les emplois du temps ont été mis à jour et la proposition d\'échange refusée en conséquence. Un mail de confirmation t\'a été envoyé'));
    elseif ($mode == 'accept')
      returnJSON(array('info' => 'Proposition acceptée avec succès. Les emplois du temps ont été mis à jour en conséquence. Un mail de confirmation t\'a été envoyé'));
    elseif ($mode == 'refuse')
      returnJSON(array('info' => 'Proposition refusée avec succès. Un mail de confirmation t\'a été envoyé'));
    elseif ($mode == 'cancelSentAll') {
      if ($nbr == 0)
        returnJSON(array('error' => 'Aucune proposition annulée'));
      elseif ($nbr == 1)
        returnJSON(array('info' => '1 seule proposition envoyée annulée avec succès. Un mail de confirmation t\'a été envoyé'));
      else
        returnJSON(array('info' => $nbr. ' propositions envoyées annulées avec succès. Un mail de confirmation t\'a été envoyé pour chaque proposition envoyée annulée'));
    }
  elseif ($mode == 'refuseAll') {
    if ($nbr == 0)
      returnJSON(array('error' => 'Aucune proposition refusée'));
    elseif ($nbr == 1)
      returnJSON(array('info' => '1 seule proposition refusée avec succès. Un mail de confirmation t\'a été envoyé'));
    else
      returnJSON(array('info' => $nbr. ' propositions refusées avec succès. Un mail de confirmation t\'a été envoyé pour chaque proposition refusée'));
  }
  }
  elseif ($result === FALSE)
    returnJSON(array('error' => 'Non valide'));
  else
    returnJSON(array('error' => $result));
?>
