<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps'.'/ressources/php/include.php');

  if (!isset($_GET['param'])) {
    echo '<div id="popupHead">Paramètres</div>
    <div class="parameters">
      <button onClick="parameters(\'exporter\');">Exporter son calendrier</button>
      <button onClick="window.open(\'http://moodle.utc.fr/login/index.php?authCAS=CAS\');">Moodle</button>
      <button onClick="window.open(\'https://assos.utc.fr/uvweb/\');">UVWeb</button>
      <button onClick="window.open(\'https://gitlab.utc.fr/simde/emploidutemps\');">Gitlab (pour bugs/recommandations)</button>
      <button onClick="window.open(\'https://\' + window.location.hostname + \'/emploidutemps\' + \'/logs/changelog.txt\');">Changelog</button>
      <button onClick="parameters(\'contacter\');">Nous contacter</button>';
//      <button onClick="window.open(\'https://\' + window.location.hostname + \'/emploidutemps\' + \'/maj.php\');">Rechercher des mises à jour</button>

    $query = $GLOBALS['bdd']->prepare('SELECT login FROM etudiants WHERE login = ?');
    $GLOBALS['bdd']->execute($query, array($_SESSION['login']));

    if ($query->rowCount() == 1) {
      $query = $GLOBALS['bdd']->prepare('SELECT desinscrit FROM etudiants WHERE login = ?');
      $GLOBALS['bdd']->execute($query, array($_SESSION['login']));

      $data = $query->fetch();

      if ($data['desinscrit'] == '0')
        echo '<button onClick="parameters(\'sedesinscrire\');"">Se désinscrire du service</button>';
      else
        echo '<button onClick="parameters(\'reinscription\');"">Se réinscrire au service</button>';
    }

    echo '<button onClick="window.location.href = \'/emploidutemps\' + \'/deconnexion.php\'">Se déconnecter</button>
    </div>';
  }
  elseif ($_GET['param'] == 'exporter') {
      echo '<div onClick="parameters()" style="cursor: pointer" id="popupHead">Exporter son calendrier (expérimental)</div>
      <div class="parameters">
        Etre prévenu <input id="alarmICS" placeholder="0" /> min avant l\'évènement (cours, TD, TP)<br />
        <button onClick="getICal();">Sous format ICal (.ics) pour son agenda Android/Google ou iOS/Apple</button>
        <button onClick="window.location.href = \'https://\' + window.location.hostname + \'/emploidutemps\' + \'/alternances.pdf\';">Obtenir le calendrier des alternances</button>
        <button onClick="parameters(\'exporter\');">Bientôt d\'autres options</button>
      </div>';
  }
  elseif ($_GET['param'] == 'sedesinscrire') {
    $query = $GLOBALS['bdd']->prepare('SELECT desinscrit FROM etudiants WHERE login = ?');
    $GLOBALS['bdd']->execute($query, array($_SESSION['login']));

    $data = $query->fetch();

    if ($data['desinscrit'] == '0')
      echo '<div onClick="parameters()" style="cursor: pointer" id="popupHead">Désinscription au service</div>
      <div class="parameters">Souhaitez-vous réellement vous désinscrire du service pour ce semestre ?<br />
      Par défaut, toutes les demandes et propositions d\'échange reçues seront annulées. Aussi, vous ne recevrez plus de mails et, plus personne ne pourra vous proposer un échange<br /><br />
      Cliquez sur le bouton suivant pour vous désinscrire: <button style="background-color: #FF0000" onClick="parameters(\'desinscription\')">Se désinscrire</button></div>';
    else
      echo '<div onClick="parameters()" style="cursor: pointer" id="popupHead">Paramètres</div>
      <div class="parameters" style="background-color: #FF0000">Désinscription déjà réalisée</div>';
  }
  elseif ($_GET['param'] == 'desinscription') {
    $envoies = getEnvoiesList($_SESSION['login'], NULL, 1);
    $recues = getRecuesList($_SESSION['login'], NULL, 1);

    foreach ($envoies as $envoie)
      cancelIdExchange($envoie['idEchange']);

    foreach ($recues as $recue)
      refuseIdExchange($recue['idEchange']);

    $query = $GLOBALS['bdd']->prepare('UPDATE etudiants SET desinscrit = 1 WHERE login = ?');
    $GLOBALS['bdd']->execute($query, array($_SESSION['login']));

    echo '<div onClick="parameters()" style="cursor: pointer" id="popupHead">Désinscription au service</div>
    <div class="parameters" style="background-color: #FF0000">Vous avez été désinscrit du service pour ce semestre avec succès !<br />
    Par défaut, toutes vos demandes et propositions reçues ont été annulées<br /><br />
    Vous ne recevrez à présent plus de mails et, plus personne ne peut vous proposer un échange</div>';
  }
  elseif ($_GET['param'] == 'reinscription') {
    $query = $GLOBALS['bdd']->prepare('UPDATE etudiants SET desinscrit = 0 WHERE login = ?');
    $GLOBALS['bdd']->execute($query, array($_SESSION['login']));

    echo '<div onClick="parameters()" style="cursor: pointer" id="popupHead">Réinscription au service</div>
    <div class="parameters" style="background-color: #00FF00">Vous avez été réinscrit au service pour ce semestre avec succès !<br /><br />
    Vous pouvez dès à présent recevoir et envoyer de nouvelles propositions d\'échange</div>';
  }
  elseif ($_GET['param'] == 'contacter') {
    echo '<div onClick="parameters()" style="cursor: pointer" id="popupHead">Nous contacter</div>
    <div class="parameters">
      <button onClick="window.open(\'mailto:simde@assos.utc.fr\');">Contacter le SIMDE</button>
      <button onClick="window.open(\'mailto:samy.nastuzzi@etu.utc.fr\');">Contacter le créateur de la page</button></div>';
  }
?>
