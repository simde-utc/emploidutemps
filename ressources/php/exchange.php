<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/include.php');

  $defaultNote = 'Je souhaiterais échanger mon UV contre la tienne.';

  function printError($error) {
    echo '<div style="background-color: #FF0000" id="popupHead">Erreur: ', $error, '</div>';
    exit;
  }

  function printSucces($succes) {
    echo '<div style="background-color: #00FF00" id="popupHead">', $succes, '</div>';
    exit;
  }

  $query = $GLOBALS['bdd']->prepare('SELECT desinscrit FROM etudiants WHERE login = ?');
  $GLOBALS['bdd']->execute($query, array($_SESSION['login']));

  $data = $query->fetch();

  if ($data['desinscrit'] == '1')
    printError('Il est impossible d\'échanger ses UVs lorsqu\'on est désinscrit du service<br /><div class="parameters"><button style="background-color: #00FF00" onClick="parameters(\'reinscription\');"">Se réinscrire au service</button></div>');
//
  if (isUpdating())
    printError('Impossible d\'échanger, le service d\'échange est temporairement suspendu');
//
  //printError('Impossible d\'échanger, le service d\'échange est temporairement suspendu. Un bug bloque l\'envoie du mail de confirmation. Désolé je fais ca au plus vite');

  if (isset($_GET['idExchange']) && is_string($_GET['idExchange']) && !empty($_GET['idExchange'])) {
    if (isset($_GET['refuse']) && is_string($_GET['refuse']) && $_GET['refuse'] == '1') { // On annonce que la proposition n'est plus dispo = refusée
      refuseIdExchange($_GET['idExchange']);
      printSucces('Proposition refusée avec succès');
    }

    elseif (isset($_GET['accept']) && is_string($_GET['accept']) && $_GET['accept'] == '1') {
      if (count(getRecuesList($_SESSION['login'], $_GET['idExchange'], 1)) == 0) // On vérifie que la personne a bien reçu la demande
        printError('La proposition n\'existe pas ou n\'existe plus');

      $envoies = getEnvoiesList(NULL, $_GET['idExchange'], 1);
      if (count($envoies) == 0) // On vérifie qu'on peut échanger avec quelqu'un
        printError('Personne n\'est disponible pour l\'échange');

      // On annonce que la personne a validé la proposition
      $query = $GLOBALS['bdd']->prepare('UPDATE recues SET date = NOW(), disponible = 0, echange = 1 WHERE login = ? AND idEchange = ?');
      $GLOBALS['bdd']->execute($query, array($_SESSION['login'], $_GET['idExchange']));

      // On annonce au plus tôt demandeur que son échange a été accepté
      $query = $GLOBALS['bdd']->prepare('UPDATE envoies SET date = NOW(), disponible = 0, echange = 1, date = NOW() WHERE login = ? AND idEchange = ?');
      $GLOBALS['bdd']->execute($query, array($envoies[0]['login'], $_GET['idExchange']));

      // S'il n'y avait qu'un seul (dernier) demandeur, on désactive l'annonce puisque plus personne d'autre demande l'échange
      if (count($envoies) == 1) {
        $query = $GLOBALS['bdd']->prepare('UPDATE echanges SET active = 0 WHERE idEchange = ?');
        $GLOBALS['bdd']->execute($query, array($_GET['idExchange']));
      }

      if (count(getRecuesList(NULL, $_GET['idExchange'], 1)) == 0) { // Si plus personne ne peut répondre
        $query = $GLOBALS['bdd']->prepare('UPDATE echanges SET active = 0 WHERE idEchange = ?');
        $GLOBALS['bdd']->execute($query, array($_GET['idExchange']));
        $query = $GLOBALS['bdd']->prepare('UPDATE envoies SET disponible = 0, date = NOW() WHERE idEchange = ? AND disponible = 1');
        $GLOBALS['bdd']->execute($query, array($_GET['idExchange']));
      }

      $infosLogin = getEtu($envoies[0]['login']);
      $infosIdUV = getUVFromIdUV($envoies[0]['idUV']);

      sendMail($_SESSION['mail'], 'Echange effectué', 'Salut !'.PHP_EOL.'Un échange a été effectué avec '.$infosLogin['nom'].' '.$infosLogin['prenom'].' (mail: '.$infosLogin['mail'].') concernant le '.($infosIdUV['type'] == 'D' ? 'TD' : ($infosIdUV['type'] == 'C' ? 'cours' : 'TP')).' de '.$infosIdUV['uv'].' !'.PHP_EOL.'Il est nécessaire de tenir au courant les responsables et chargés de l\'UV'.PHP_EOL.PHP_EOL.'Ton emploi du temps a été mis à jour (uniquement sur le site) !');
      sendMail($infosLogin['mail'], 'Echange effectué', 'Salut !'.PHP_EOL.'Un échange a été effectué avec '.$_SESSION['nom'].' '.$_SESSION['prenom'].' (mail: '.$_SESSION['mail'].') concernant le '.($infosIdUV['type'] == 'D' ? 'TD' : ($infosIdUV['type'] == 'C' ? 'cours' : 'TP')).' de '.$infosIdUV['uv'].' !'.PHP_EOL.'Il est nécessaire de tenir au courant les responsables et chargés de l\'UV'.PHP_EOL.PHP_EOL.'Ton emploi du temps a été mis à jour (uniquement sur le site) !');

      // On rend les UVs échangées non actuel
      $query = $GLOBALS['bdd']->prepare('UPDATE cours SET actuel = 0, echange = 1 WHERE login = ? AND id = ?');
      $GLOBALS['bdd']->execute($query, array($_SESSION['login'], $envoies[0]['pour']));
      $GLOBALS['bdd']->execute($query, array($envoies[0]['login'], $envoies[0]['idUV']));

      // On regarde si on a pas déjà été inscrit à l'UV
      $check = $GLOBALS['bdd']->prepare('SELECT * FROM cours WHERE login = ? AND id = ?');
      // On ajoute aux edt l'UV récupéré
      $insert = $GLOBALS['bdd']->prepare('INSERT INTO cours (login, id, actuel, echange) VALUES (?, ?, 1, 1)');
      $update = $GLOBALS['bdd']->prepare('UPDATE cours SET actuel = 1, echange = 1 WHERE login = ? AND id = ?');
      $GLOBALS['bdd']->execute($check, array($_SESSION['login'], $envoies[0]['idUV']));
      $GLOBALS['bdd']->execute(($check->rowCount() == 0) ? $insert : $update, array($_SESSION['login'], $envoies[0]['idUV']));
      $GLOBALS['bdd']->execute($check, array($envoies[0]['login'], $envoies[0]['pour']));
      $GLOBALS['bdd']->execute(($check->rowCount() == 0) ? $insert : $update, array($envoies[0]['login'], $envoies[0]['pour']));

      // On supprime toutes les autres demandes
      $data = getEnvoiesList($envoies[0]['login'], NULL, 1, 0, $envoies[0]['idUV']);
      foreach ($data as $envoie)
        cancelIdExchange($envoie['idEchange'], $envoies[0]['login']);

      $data = getEnvoiesList($_SESSION['login'], NULL, 1, 0, $envoies[0]['pour']);
      foreach ($data as $envoie)
        cancelIdExchange($envoie['idEchange']);

      // Toutes les demandes reçues sur un créneau doivent être attribuées à son possesseur
      $data = getRecuesList($login, NULL, 1, 0, NULL, $envoies[0]['idUV']);
      $query = $GLOBALS['bdd']->prepare('UPDATE recues SET login = ? WHERE idEchange = ? AND login = ?');
      foreach ($data as $recue)
        $GLOBALS['bdd']->execute($query, array($_SESSION['login'], $recue['idEchange'], $envoies[0]['login']));

      $data = getRecuesList($_SESSION['login'], NULL, 1, 0, NULL, $envoies[0]['pour']);
      $query = $GLOBALS['bdd']->prepare('UPDATE recues SET login = ? WHERE idEchange = ? AND login = ?');
       foreach ($data as $recue)
        $GLOBALS['bdd']->execute($query, array($envoies[0]['login'], $recue['idEchange'], $_SESSION['login']));

      printSucces('Proposition acceptée avec succès. Les emplois du temps ont été mis à jour (uniquement sur le site)');
    }

    elseif (isset($_GET['del']) && is_string($_GET['del']) && $_GET['del'] == '1') {
      $envoie = getEnvoiesList($_SESSION['login'], $_GET['idExchange'], 1);
      // On vérifie bien que notre demande est active
      if (count($envoie) == 0)
        printError('Impossible de supprimer la proposition');
      // On la supprime
      cancelIdExchange($_GET['idExchange']);

      printSucces('Proposition supprimée avec succès');
    }

    elseif (isset($_GET['cancel']) && $_GET['cancel'] == '1' && isset($_GET['idExchange']) && is_string($_GET['idExchange'])) {
      $envoi = getEnvoiesList($_SESSION['login'], $_GET['idExchange'], 0, 1);
      $recu = getRecuesList($_SESSION['login'], $_GET['idExchange'], 0, 1);

      if (count($envoi) == 1) {
        // On récupère avec qui on a échangé
        $recu = getRecuesList(NULL, $_GET['idExchange'], NULL, 1, NULL, NULL, $envoi[0]['date']);
        $infosIdUV = getUVFromIdUV($recu[0]['idUV']);

        if (count($recu) == 1) {
          $etuInfo = getEtu($recu[0]['login']);
          // On indique la demande d'annulation
          $query = $GLOBALS['bdd']->prepare('UPDATE envoies SET disponible = 1 WHERE idEchange = ? AND login = ?');
          $GLOBALS['bdd']->execute($query, array($_GET['idExchange'], $_SESSION['login']));
          // Si on est le premier à demander l'annulation
          if ($recu[0]['disponible'] == 0) {
            echo 'a';
            print_r($etuInfo);
            sendMail($_SESSION['mail'], 'Votre demande d\'annulation', 'Salut !'.PHP_EOL.'Tu as demandé d\'annuler le changement du '.($infosIdUV['type'] == 'D' ? 'TD' : ($infosIdUV['type'] == 'C' ? 'cours' : 'TP')).' de '.$infosIdUV['uv'].'. Un mail a déjà été envoyé mais tu peux le/la contacter directement si besoin: '.$etuInfo['mail']);
            sendMail($etuInfo['mail'], 'Demande d\'annulation', 'Salut !'.PHP_EOL.$_SESSION['nom'].' '.$_SESSION['prenom'].' souhaiterait annuler le '.($infosIdUV['type'] == 'D' ? 'TD' : ($infosIdUV['type'] == 'C' ? 'cours' : 'TP')).' de '.$infosIdUV['uv'].' l\'échange que vous avez effectué ensemble'.PHP_EOL.(isset($_POST['note']) ? 'Voici la raison qu\'il a donné: '.PHP_EOL.$_POST['note'].PHP_EOL : '').'Pour annuler: https://assos.utc.fr/emploidutemps/?mode=modifier&recu=1&id=r'.$_GET['idExchange'], $_SESSION['mail']);
            printSucces('Demande d\'annulation envoyée avec succès !');
          }
          else { // On effectue l'annulation
            // On supprime la demande d'envoi
            $query = $GLOBALS['bdd']->prepare('DELETE FROM envoies WHERE disponible = 1 AND echange = 1 AND idEchange = ? AND login = ?');
            $GLOBALS['bdd']->execute($query, array($_GET['idExchange'], $_SESSION['login']));
            // On réinitialise la demande d'échange reçu
            $query = $GLOBALS['bdd']->prepare('UPDATE recues SET disponible = 1, echange = 0 WHERE idEchange = ? AND login = ?');
            $GLOBALS['bdd']->execute($query, array($_GET['idExchange'], $recu[0]['login']));
            // On redonne les créneaux de chacun
            $query = $GLOBALS['bdd']->prepare('UPDATE cours SET actuel = 1, echange = 0 WHERE id = ? AND login = ?');
            $GLOBALS['bdd']->execute($query, array($envoi[0]['idUV'], $_SESSION['login']));
            $GLOBALS['bdd']->execute($query, array($envoi[0]['pour'], $recu[0]['login']));
            // On supprime les créneaux échangés
            $query = $GLOBALS['bdd']->prepare('DELETE FROM cours WHERE actuel = 1 AND echange = 1 AND id = ? AND login = ?');
            $GLOBALS['bdd']->execute($query, array($envoi[0]['pour'], $_SESSION['login']));
            $GLOBALS['bdd']->execute($query, array($envoi[0]['idUV'], $recu[0]['login']));
            // On réatribue les annonces à l'un et à l'autres parce que toutes les demandes reçues sur un créneau doivent être attribuées à son possesseur
            $data = getRecuesList($recu[0]['login'], NULL, 1, 0, NULL, $envoi[0]['idUV']);
            $query = $GLOBALS['bdd']->prepare('UPDATE recues SET login = ? WHERE idEchange = ? AND login = ?');
            foreach ($data as $recue)
              $GLOBALS['bdd']->execute($query, array($_SESSION['login'], $recue['idEchange'], $recu[0]['login']));
            $data = getRecuesList($_SESSION['login'], NULL, 1, 0, NULL, $envoi[0]['pour']);
            $query = $GLOBALS['bdd']->prepare('UPDATE recues SET login = ? WHERE idEchange = ? AND login = ?');
             foreach ($data as $recue)
              $GLOBALS['bdd']->execute($query, array($recu[0]['login'], $recue['idEchange'], $_SESSION['login']));

            sendMail($_SESSION['mail'], 'Annulation d\'échange', 'Salut !'.PHP_EOL.'Ton échange avec '.$etuInfo['nom'].' '.$etuInfo['prenom'].' pour le '.($infosIdUV['type'] == 'D' ? 'TD' : ($infosIdUV['type'] == 'C' ? 'cours' : 'TP')).' de '.$infosIdUV['uv'].' a été annulé');
            sendMail($etuInfo['mail'], 'Annulation d\'échange', 'Salut !'.PHP_EOL.'Ton échange avec '.$_SESSION['nom'].' '.$_SESSION['prenom'].' pour le '.($infosIdUV['type'] == 'D' ? 'TD' : ($infosIdUV['type'] == 'C' ? 'cours' : 'TP')).' de '.$infosIdUV['uv'].' a été annulé');
            printSucces('Les deux emplois du temps ont été restaurés. L\'échange a été annulé avec succès !');
          }
        }
        else
          printError('Bizarrement, personne n\'a echangé cette UV.... #Improbable');
      }
      elseif (count($recu) == 1) {
        // On récupère avec qui on a échangé
        $envoi = getEnvoiesList(NULL, $_GET['idExchange'], NULL, 1, NULL, NULL, $recu[0]['date']);
        $infosIdUV = getUVFromIdUV($envoi[0]['idUV']);

        if (count($envoi) == 1) {
          $etuInfo = getEtu($envoi[0]['login']);
          // On indique la demande d'annulation
          $query = $GLOBALS['bdd']->prepare('UPDATE recues SET disponible = 1 WHERE idEchange = ? AND login = ?');
          $GLOBALS['bdd']->execute($query, array($_GET['idExchange'], $_SESSION['login']));
          // Si on est le premier à demander l'annulation
          if ($envoi[0]['disponible'] == 0) {
            sendMail($_SESSION['mail'], 'Votre demande d\'annulation', 'Salut !'.PHP_EOL.'Tu as demandé d\'annuler le changement du '.($infosIdUV['type'] == 'D' ? 'TD' : ($infosIdUV['type'] == 'C' ? 'cours' : 'TP')).' de '.$infosIdUV['uv'].'. Un mail a déjà été envoyé mais tu peux le/la contacter directement si besoin: '.$etuInfo['mail']);
            sendMail($etuInfo['mail'], 'Demande d\'annulation', 'Salut !'.PHP_EOL.$_SESSION['nom'].' '.$_SESSION['prenom'].' souhaiterait annuler le '.($infosIdUV['type'] == 'D' ? 'TD' : ($infosIdUV['type'] == 'C' ? 'cours' : 'TP')).' de '.$infosIdUV['uv'].' l\'échange que vous avez effectué ensemble'.PHP_EOL.(isset($_POST['note']) ? 'Voici la raison qu\'il a donné: '.PHP_EOL.$_POST['note'].PHP_EOL : '').'Pour annuler: https://assos.utc.fr/emploidutemps/?mode=modifier&envoi=1&id=e'.$_GET['idExchange'], $_SESSION['mail']);
            printSucces('Demande d\'annulation envoyée avec succès !');
          }
          else { // On effectue l'annulation
            // On supprime la demande d'envoi
            $query = $GLOBALS['bdd']->prepare('DELETE FROM envoies WHERE disponible = 1 AND echange = 1 AND idEchange = ? AND login = ?');
            $GLOBALS['bdd']->execute($query, array($_GET['idExchange'], $envoi[0]['login']));
            // On réinitialise la demande d'échange reçu
            $query = $GLOBALS['bdd']->prepare('UPDATE recues SET disponible = 1, echange = 0 WHERE idEchange = ? AND login = ?');
            $GLOBALS['bdd']->execute($query, array($_GET['idExchange'], $_SESSION['login']));
            // On redonne les créneaux de chacun
            $query = $GLOBALS['bdd']->prepare('UPDATE cours SET actuel = 1, echange = 0 WHERE id = ? AND login = ?');
            $GLOBALS['bdd']->execute($query, array($recu[0]['pour'], $_SESSION['login']));
            $GLOBALS['bdd']->execute($query, array($recu[0]['idUV'], $envoi[0]['login']));
            // On supprime les créneaux échangés
            $query = $GLOBALS['bdd']->prepare('DELETE FROM cours WHERE actuel = 1 AND echange = 1 AND id = ? AND login = ?');
            $GLOBALS['bdd']->execute($query, array($recu[0]['idUV'], $_SESSION['login']));
            $GLOBALS['bdd']->execute($query, array($recu[0]['pour'], $envoi[0]['login']));
            // On réatribue les annonces à l'un et à l'autres parce que toutes les demandes reçues sur un créneau doivent être attribuées à son possesseur
            $data = getRecuesList($_SESSION['login'], NULL, 1, 0, NULL, $recu[0]['idUV']);
            $query = $GLOBALS['bdd']->prepare('UPDATE recues SET login = ? WHERE idEchange = ? AND login = ?');
            foreach ($data as $recue)
              $GLOBALS['bdd']->execute($query, array($envoi[0]['login'], $recue['idEchange'], $_SESSION['login']));
            $data = getRecuesList($envoi[0]['login'], NULL, 1, 0, NULL, $recu[0]['pour']);
            $query = $GLOBALS['bdd']->prepare('UPDATE recues SET login = ? WHERE idEchange = ? AND login = ?');
             foreach ($data as $recue)
              $GLOBALS['bdd']->execute($query, array($_SESSION['login'], $recue['idEchange'], $envoi[0]['login']));

            sendMail($_SESSION['mail'], 'Annulation d\'échange', 'Salut !'.PHP_EOL.'Ton échange avec '.$etuInfo['nom'].' '.$etuInfo['prenom'].' pour le '.($infosIdUV['type'] == 'D' ? 'TD' : ($infosIdUV['type'] == 'C' ? 'cours' : 'TP')).' de '.$infosIdUV['uv'].' a été annulé');
            sendMail($etuInfo['mail'], 'Annulation d\'échange', 'Salut !'.PHP_EOL.'Ton échange avec '.$_SESSION['nom'].' '.$_SESSION['prenom'].' pour le '.($infosIdUV['type'] == 'D' ? 'TD' : ($infosIdUV['type'] == 'C' ? 'cours' : 'TP')).' de '.$infosIdUV['uv'].' a été annulé');
            printSucces('Les deux emplois du temps ont été restaurés. L\'échange a été annulé avec succès !');
          }
        }
        else
          printError('Bizarrement, personne n\'a echangé cette UV.... #Improbable');
      }
      else
        printError('Impossible d\'annuler');
    }

    elseif (isset($_GET['infos']) && is_string($_GET['infos']) && $_GET['infos'] == '1') {
      $envoies = getEnvoiesList(NULL, $_GET['idExchange']);
      // On vérifie bien que la proposition a déjà été demandée
      if (count($envoies) == 0)
        printError('Aucune offre n\'est disponible');
      // On récup les infos concernant les idUVs
      $idUV = getUVFromIdUV($envoies[0]['idUV']);
      $for = getUVFromIdUV($envoies[0]['pour']);
      // Affichage d'un récap de l'échange
      echo '<div id="popupHead">Proposition d\'échange du ', ($idUV['type'] == 'D' ? $idUV['type'] = 'TD' : ($idUV['type'] == 'C' ? $idUV['type'] = 'cours' : $idUV['type'] = 'TP')), ' de ', $idUV['uv'], '<br />Le ', $jours[$idUV['jour']], ' de ', $idUV['debut'], ' à ', $idUV['fin'], ' en ', $idUV['salle'], (($idUV['semaine'] == '') ? '' : ' chaque semaine '.$idUV['semaine']), ' contre celui du ', $jours[$for['jour']], ' de ', $for['debut'], ' à ', $for['fin'], ' en ', $for['salle'], (($for['semaine'] == '') ? '' : ' chaque semaine '.$for['semaine']).'</div>';
      echo '<div id="searchResult">';

      foreach ($envoies as $envoie) { // Afficher la demande en fonction de son état
        if ($envoie['disponible'] == 1 && $envoie['echange'] == 0) {
          $bgColor = '#0000FF';
          $etuInfos = getEtu($envoie['login']);
          $note = 'Demande de '.$etuInfos['nom'].' '.$etuInfos['prenom'];
        }
        elseif ($envoie['disponible'] == 0 && $envoie['echange'] == 1) {
          $bgColor = '#00FF00';
          $note = 'Echangé le '.$envoie['date'];
        }
        else {
          $bgColor = '#FF0000';
          $note = 'Refusé';
        }

        $fgColor = getFgColor($bgColor);

        echo '<div class="searchCard" style="width: 100%; background-color: ', $bgColor, '; color: ', $fgColor, '">',
        '<div class="nameCard">', $note, '</div>',
        '<div class="noteCard">', $envoie['note'], '</div></div>';
      }

      echo '</div>';
    }
    else
      printError('Aucune demande réalisée');
  }

  // Si on souhaite ajouter un échange
  elseif (isset($_GET['idUV']) && is_string($_GET['idUV']) && !empty($_GET['idUV']) && isset($_GET['for']) && is_string($_GET['for']) && !empty($_GET['for'])) {
    $sessionInfo = getEtu($_SESSION['login']);
    $idUV = getUVFromIdUV($_GET['idUV']);
    $for = getUVFromIdUV($_GET['for']);
    // Vérifications
    if ($_GET['idUV'] == $_GET['for'])
      printError('Echange impossible avec la même UV');

    if ($idUV['uv'] != $for['uv'])
      printError('Les échanges se font uniquement entre la même UV');

    $edts = getEdtEtu($_SESSION['login']);

    $notIn = TRUE;
    foreach ($edts as $edt) {
      if ($edt['id'] == $_GET['idUV'])
        $notIn = FALSE;
    }

    if ($notIn)
      printError('Les échanges ne peuvent être effectués que si les échangeurs possèdent les UVs concernées');

    // Demander d'ajouter
    if (isset($_GET['ask']) && is_string($_GET['ask']) && !empty($_GET['ask'])) {
      // On vérifie qu'on nous a pas déjà demandé d'échanger avant de refaire une demande
      $dejaRecu = getRecuesList($_SESSION['login'], NULL, 1, NULL, $_GET['for'], $_GET['idUV']);
      if (count($dejaRecu) == 1 && count(getEchange($dejaRecu[0]['idUV'], $dejaRecu[0]['pour'], 1)) == 1)
        printError('Une demande d\'échange a déjà été réalisée pour ce créneau<br /><button onClick="window.location.href = \'https://\' + window.location.hostname + window.location.pathname + \'?mode=modifier&recu=1&id=r'.$dejaRecu[0]['idEchange'].'\'">Consulter la proposition</button>');

      // On vérifie qu'on a pas déjà changer nos deux créneaux, et rediriger vers l'annulation de changement
      $checkEnvoi = getEnvoiesList($_SESSION['login'], NULL, NULL, NULL, $_GET['for'], $_GET['idUV']);
      $checkRecu = getRecuesList($_SESSION['login'], NULL, NULL, NULL, $_GET['idUV'], $_GET['for']);
      if (count($checkEnvoi) == 1)
          printError('Ces deux créneaux ont déjà été interchangés. Il est possible de demander d\'annuler un échange<br /><button onClick="window.location.href = \'https://\' + window.location.hostname + window.location.pathname + \'?mode=modifier&envoi=1&id=e'.$checkEnvoi[0]['idEchange'].'\'">Annuler l\'échange</button>');
      elseif (count($checkRecu) == 1)
          printError('Ces deux créneaux ont déjà été interchangés. Il est possible de demander d\'annuler un échange<br /><button onClick="window.location.href = \'https://\' + window.location.hostname + window.location.pathname + \'?mode=modifier&recu=1&id=r'.$checkRecu[0]['idEchange'].'\'">Annuler l\'échange</button>');

      $existedExchange = getEchange($_GET['idUV'], $_GET['for'], 0);
      $etuList = getEtuFromIdUV($_GET['for'], 0);

      if (count($existedExchange) == 0) { // On regarde si la proposition est active ou inexsitante
        $existedExchange = getEchange($_GET['idUV'], $_GET['for']);
        // S'il existe déjà, voir si on a pas déjà fait la demande
        if (count($existedExchange) != 0 && count(getEnvoiesList($_SESSION['login'], $existedExchange[0]['idEchange'])) == 1)
          printError('La proposition a déjà été réalisée');

        // On vérifie que certains sont inscrits
        if (count($etuList) == 0)
          printError('Personne ne souhaite recevoir de proposition d\'échange pour ce créneau');
      }
      else { // La proposition est inactive
        // On vérifie si tout le monde a refusé la proposition
        if (count(getRecuesList(NULL, $existedExchange[0]['idEchange'])) == 0)
          printError('Cette proposition a déjà été refusée par tout le monde');
      }

      $note = '<br />Attention, cette proposition a déjà été faite';
      $nbrEnvoi = (count($existedExchange) == 0 ? 0 : count(getEnvoiesList(NULL, $existedExchange[0]['idEchange'])));

      echo '<div id="popupHead">Proposition d\'échange du ', ($idUV['type'] == 'D' ? $idUV['type'] = 'TD' : ($idUV['type'] == 'C' ? $idUV['type'] = 'cours' : $idUV['type'] = 'TP')), ' de ', $idUV['uv'], '<br />Le ', $jours[$idUV['jour']], ' de ', $idUV['debut'], ' à ', $idUV['fin'], ' en ', $idUV['salle'], (($idUV['semaine'] == '') ? '' : ' chaque semaine '.$idUV['semaine']), ' contre celui du ', $jours[$for['jour']], ' de ', $for['debut'], ' à ', $for['fin'], ' en ', $for['salle'], (($for['semaine'] == '') ? '' : ' chaque semaine '.$for['semaine']), '<br />',
      ($nbrEnvoi == 0 ? '' : '<br />Attention, cette demande a déjà été proposée '.$nbrEnvoi.' fois'), '<br />Voulez-vous réellement proposer aux ', count($etuList), ' étudiant', (count($etuList) == 1 ? '' : 's'), ' cette échange ?</div>',
      '<textarea maxlength="500" cols="30" rows="5" id="noteExchange" contenteditable>', $defaultNote, '</textarea><br />
      <input type="button" id="sendExchange" value="Proposer l\'échange" onClick="addExchange(', $_GET['idUV'], ', ', $_GET['for'], ', $(\'#noteExchange\').val())"/></div>';
    }

    elseif (isset($_GET['add']) && is_string($_GET['add']) && !empty($_GET['add']) && isset($_POST['note']) && is_string($_POST['note'])) {
      // On vérifie qu'on nous a pas déjà demandé d'échanger avant de refaire une demande
      $dejaRecu = getRecuesList($_SESSION['login'], NULL, 1, NULL, $_GET['for'], $_GET['idUV']);
      if (count($dejaRecu) == 1 && count(getEchange($dejaRecu[0]['idUV'], $dejaRecu[0]['pour'], 1)) == 1)
        printError('Une demande d\'échange a déjà été réalisée pour ce créneau<br /><button onClick="window.location.href = \'https://\' + window.location.hostname + window.location.pathname + \'?mode=modifier&recu=1&id=r'.$dejaRecu[0]['idEchange'].'\'">Consulter la proposition</button>');

      // On vérifie qu'on a pas déjà changer nos deux créneaux, et rediriger vers l'annulation de changement
      $checkEnvoi = getEnvoiesList($_SESSION['login'], NULL, NULL, NULL, $_GET['for'], $_GET['idUV']);
      $checkRecu = getRecuesList($_SESSION['login'], NULL, NULL, NULL, $_GET['idUV'], $_GET['for']);
      if (count($checkEnvoi) == 1)
          printError('Ces deux créneaux ont déjà été interchangés. Il est possible de demander d\'annuler un échange<br /><button onClick="window.location.href = \'https://\' + window.location.hostname + window.location.pathname + \'?mode=modifier&envoi=1&id=e'.$checkEnvoi[0]['idEchange'].'\'">Annuler l\'échange</button>');
      elseif (count($checkRecu) == 1)
          printError('Ces deux créneaux ont déjà été interchangés. Il est possible de demander d\'annuler un échange<br /><button onClick="window.location.href = \'https://\' + window.location.hostname + window.location.pathname + \'?mode=modifier&recu=1&id=r'.$checkRecu[0]['idEchange'].'\'">Annuler l\'échange</button>');

      $note = (empty($_POST['note']) ? $defaultNote : $_POST['note']);
      $existedExchange = getEchange($_GET['idUV'], $_GET['for'], 0);

      if (count($existedExchange) == 0) // On regarde si la proposition est active ou inexistante
        $existedExchange = getEchange($_GET['idUV'], $_GET['for']);
      else { // On vérifie ici si la proposition a déjà été proposée mais est inactive
        // On vérifie si tout le monde a refusé la proposition
        if (count(getRecuesList(NULL, $existedExchange['idEchange'])) == 0)
          printError('Cette proposition a déjà été refusée par tout le monde');
        // On réactive la proposition

        $query = $GLOBALS['bdd']->prepare('UPDATE echanges SET active = 1 WHERE idEchange = ?');
        $GLOBALS['bdd']->execute($query, array($existedExchange[0]['idEchange']));
      }

      // On regarde si la proposition n'existe pas
      if (count($existedExchange) == 0) {
        $etuList = getEtuFromIdUV($_GET['for'], 0);

        if (count($etuList) == 0) // On vérifie que certains sont inscrits
          printError('Personne ne souhaite recevoir de proposition d\'échange');

        $query = $GLOBALS['bdd']->prepare('INSERT INTO echanges (idUV, pour) VALUES (?, ?)');
        $GLOBALS['bdd']->execute($query, array($_GET['idUV'], $_GET['for']));
        $data = getEchange($_GET['idUV'], $_GET['for']);

        $idExchange = $data[0]['idEchange'];

        // On fait la demande aux personnes inscrites à cet idUV
        $query = $GLOBALS['bdd']->prepare('INSERT INTO recues (login, date, idEchange) VALUES (?, NOW(), ?)');

        $infosIdUV =  getUVFromIdUV($_GET['idUV']);
        // On vérifie que chaque étudiant n'est pas désinscrit au service et qu'il suit toujours cet idUV
        foreach ($etuList as $etu) {
          if ($etu['desinscrit'] == 0 && $etu['actuel'] == 1) {
            $GLOBALS['bdd']->execute($query, array($etu['login'], $idExchange));
            sendMail($etu['mail'], 'Nouvelle demande d\'échange', 'Salut !'.PHP_EOL.'Tu as reçu une nouvelle demande d\'échange pour le '.($infosIdUV['type'] == 'D' ? 'TD' : ($infosIdUV['type'] == 'C' ? 'cours' : 'TP')).' de '.$infosIdUV['uv'].' !'.PHP_EOL.'Tu peux consulter la proposition ici: https://assos.utc.fr/emploidutemps/?mode=modifier&recu=nouveau&id=r'.$idExchange.PHP_EOL.'Fais attention, cela ce joue au shotgun !');
          }
        }
      }
      else {
        $idExchange = $existedExchange[0]['idEchange'];
        // S'il existe déjà, voir si on a pas déjà fait la demande
        if (count(getEnvoiesList($_SESSION['login'], $idExchange)) == 1)
          printError('La proposition a déjà été réalisée');
      }

      // On insère notre demande
      $query = $GLOBALS['bdd']->prepare('INSERT INTO envoies (login, idEchange, date, note) VALUES (?, ?, NOW(), ?)');
      $GLOBALS['bdd']->execute($query, array($_SESSION['login'], $idExchange, $note));

      sendMail($SESSION['mail'], 'Demande d\'échange', 'Salut !'.PHP_EOL.'Ta demande d\'échange a été envoyée avec succès pour le '.($infosIdUV['type'] == 'D' ? 'TD' : ($infosIdUV['type'] == 'C' ? 'cours' : 'TP')).' de '.$infosIdUV['uv'].' !'.PHP_EOL.'Tu recevras une notification dès que quelqu\'un a validé l\'échange !');
      printSucces('Votre proposition d\'échange a été ajoutée avec succès');
    }
    else
      printError('Aucune demande d\'échange réalisée');
  }
  else
    printError('Impossible de lancer le protocole d\'échange');
?>
