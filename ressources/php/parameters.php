<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps'.'/ressources/php/include.php');

  if (!isset($_GET['param'])) {
    echo '<div id="popupHead">Paramètres</div>
    <div class="parameters">
      <button onClick="parameters(\'exporter\');"><i class="fa fa-download" aria-hidden="true"></i> Exporter/Télécharger</button>
      <button onClick="parameters(\'aide\');"><i class="fa fa-info" aria-hidden="true"></i> Aide</button>
      <div style="display: flex; margin-left: 10%; margin-right: 10%; justify-content: space-between;">
        <button style="display: inline; margin-left: 0;" onClick="window.open(\'http://moodle.utc.fr/login/index.php?authCAS=CAS\');"><i class="fa fa-external-link" aria-hidden="true"></i> Moodle</button>
        <button style="display: inline; margin-left: 5px;" onClick="window.open(\'https://assos.utc.fr/uvweb/\');"><i class="fa fa-external-link" aria-hidden="true"></i> UVWeb</button>
        <button style="display: inline; margin-left: 5px;" onClick="window.open(\'https://gitlab.utc.fr/simde/emploidutemps\');"><i class="fa fa-external-link" aria-hidden="true"></i> Gitlab</button>
      </div>
      <div style="display: flex; margin-left: 10%; margin-right: 10%; justify-content: space-between;">
        <button style="display: inline; margin-left: 0;" onClick="window.open(\'https://\' + window.location.hostname + \'/emploidutemps\' + \'/logs/changelog.txt\');"><i class="fa fa-file-text-o" aria-hidden="true"></i> Changelog</button>
        <button style="display: inline; margin-left: 5px;" onClick="parameters(\'checkUpdate\');"><i class="fa fa-refresh" aria-hidden="true"></i> Chercher une màj (indisponible pour le moment)</button>
      </div>
      <button onClick="parameters(\'contacter\');"><i class="fa fa-envelope-o" aria-hidden="true"></i> Nous contacter</button>';
//      <button onClick="window.open(\'https://\' + window.location.hostname + \'/emploidutemps\' + \'/maj.php\');">Rechercher des mises à jour</button>

    $query = $GLOBALS['bdd']->prepare('SELECT login FROM etudiants WHERE login = ?');
    $GLOBALS['bdd']->execute($query, array($_SESSION['login']));

    if ($query->rowCount() == 1) {
      $query = $GLOBALS['bdd']->prepare('SELECT desinscrit FROM etudiants WHERE login = ?');
      $GLOBALS['bdd']->execute($query, array($_SESSION['login']));

      $data = $query->fetch();

      if ($data['desinscrit'] == '0')
        echo '<button onClick="parameters(\'sedesinscrire\');""><i class="fa fa-times" aria-hidden="true"></i> Se désinscrire du service</button>';
      else
        echo '<button onClick="parameters(\'reinscription\');""><i class="fa fa-check" aria-hidden="true"></i> Se réinscrire au service</button>';
    }

    echo '<button onClick="window.location.href = \'/emploidutemps\' + \'/deconnexion.php\'"><i class="fa fa-sign-out" aria-hidden="true"></i> Se déconnecter</button>
    </div>';
  }
  elseif ($_GET['param'] == 'exporter') {
      echo '<div onClick="parameters()" style="cursor: pointer" id="popupHead">Exporter/Télécharger</div>
      <div class="parameters" style="text-align: center;">
        Etre prévenu <input class="focusedInput submitedInput" type="number" step="1" min="0" max="1440" id="alarmICS" placeholder="0" /> min avant l\'évènement (cours, TD, TP)<br />
        <button class="submitedButton" onClick="getICal();">Son calendrier sous format ICal (.ics) pour son agenda Android/Google ou iOS/Apple</button>
        <button onClick="return xepOnline.Formatter.Format(\'skeduler-container\',
            {render:\'download\', pageWidth:\'216mm\', pageHeight:\'279mm\'});">Son calendrier sous format PDF (pas fini)</button>
        <button onClick="window.location.href = \'https://\' + window.location.hostname + \'/emploidutemps\' + \'/ressources/pdf/alternances.pdf\';">Obtenir le calendrier des alternances</button>
        <button onClick="window.location.href = \'https://\' + window.location.hostname + \'/emploidutemps\' + \'/ressources/pdf/infosRentree.pdf\';">Obtenir les infos de rentrée</button>
        <button>Bientôt d\'autres options</button>
      </div>';
  }
  elseif ($_GET['param'] == 'aide') {
    echo '<div onClick="parameters()" style="cursor: pointer" id="popupHead">Aide</div>
    <div class="parameters">Prochainement, un formulaire d\'aide sera créé pour faciliter la navigation sur le site<br />
    <button onClick="parameters(\'nouveau\')">Consulter le mot de bienvenu</button></div>';
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
  else if ($_GET['param'] == 'checkUpdate') {
    include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/class/class.maj.php');
    if (MAJ::checkModcasid($curl)) {
      if (MAJ::checkUpdate($curl))
        echo '<div onClick="parameters()" style="cursor: pointer" id="popupHead">Recherche de mise à jour</div>
        <div class="parameters">
          Une mise à jour de la base de donnée est disponible. En cliquant sur le bouton suivant, le serveur téléchargera automatiquement les nouveaux emplois du temps
          <button class="submitedButton" style="background-color: #00FF00" onClick="parameters(\'update\')">Mettre à jour</button>
        </div>';
      else
        echo '<div onClick="parameters()" style="cursor: pointer" id="popupHead">Recherche d\'une mise à jour</div>
        <div class="parameters" style="background-color: #FF0000">
          Aucune mise à jour disponible<br />
          Si les nouveaux emplois du temps sont disponibles, il faut réessayer plus tard
        </div>';
    }
    else
      echo '<div onClick="parameters()" style="cursor: pointer" id="popupHead">Recherche de mise à jour</div>
      <div class="parameters"', (isset($_SESSION['MODCASID']) ? ' style="background-color: #FF0000"' : ''), '>
      ', (isset($_SESSION['MODCASID']) ? 'Erreur: MODCASID incorrect !<br /><br />
      ' : ''), 'Pour effectuer une recherche de mise à jour, il est nécessaire de renseigner le cookie MODCASID<br />
        Il est possible de le récupérer en allant sur ce lien: <a href="http://wwwetu.utc.fr/sme">Accéder au SME</a><br /><br />
        Ensuite, il faut manuellement récupérer le cookie et le copier ici: <input class="focusedInput submitedInput" placeholder="MODCASID" id="modcasid" /><button class="submitedButton" onClick="$.get(\'https://\' + window.location.hostname + \'/emploidutemps\' + \'/?MODCASID=\' + $(\'#modcasid\').val(), function() { parameters(\'checkUpdate\'); });">Relancer la recherche d\'une mise à jour</button>
      </div>';
  }
  else if ($_GET['param'] == 'update') {
    include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/class/class.maj.php');
    echo $_SESSION['MODCASID'];
    if (MAJ::checkModcasid($curl)) {
      if (MAJ::update($curl))
        header('Location: /emploidutemps/');
      else
        echo '<script type="text/javascript">function refresh() { window.location.href=window.location.href } setTimeout("refresh()", 250);</script>';
    }
    else
      echo '<div onClick="parameters()" style="cursor: pointer" id="popupHead">Mise à jour</div>
      <div class="parameters"', (isset($_SESSION['MODCASID']) ? ' style="background-color: #FF0000"' : ''), '>
      ', (isset($_SESSION['MODCASID']) ? 'Erreur: MODCASID incorrect !<br /><br />
      ' : ''), 'Pour effectuer une recherche de mise à jour, il est nécessaire de renseigner le cookie MODCASID<br />
        Il est possible de le récupérer en allant sur ce lien: <a href="http://wwwetu.utc.fr/sme">Accéder au SME</a><br /><br />
        Ensuite, il faut manuellement récupérer le cookie et le copier ici: <input class="focusedInput submitedInput" placeholder="MODCASID" id="modcasid" /><button class="submitedButton" onClick="$.get(\'https://\' + window.location.hostname + \'/emploidutemps\' + \'/?MODCASID=\' + $(\'#modcasid\').val()); parameters(\'update\');">Mettre à jour</button>
      </div>';
  }
  elseif ($_GET['param'] == 'contacter') {
    echo '<div onClick="parameters()" style="cursor: pointer" id="popupHead">Nous contacter</div>
    <div class="parameters">
      <button onClick="window.open(\'mailto:simde@assos.utc.fr\');">Contacter le SIMDE</button>
      <button onClick="window.open(\'mailto:samy.nastuzzi@etu.utc.fr\');">Contacter le créateur de la page</button></div>';
  }
  elseif ($_GET['param'] == 'probleme') {
    echo '<div onClick="parameters()" style="cursor: pointer" id="popupHead">Problème technique</div>
    <div class="parameters">
      Salut ! Si tu viens consulter cette page c\'est à coup sûr pour échanger ton emploi du temps mais malheuresement il y a un problème pour récupérer les nouveaux emplois du temps auprès de la DSI<br /><br />
      <br />
      <span style="color: #FF0000;">Je viens de tout recevoir, je met à jour dans les heures à venir (nouveau format de données)</span><br />
      <br />
      Je posterai un message sur le groupe UTC =) dès que c\'est dispo !<br />
      La bise,<br />
      Samy
    </div>';
  }
  elseif ($_GET['param'] == 'nouveau') {
    echo '<div onClick="parameters()" style="cursor: pointer" id="popupHead">Bienvenu sur Emploi d\'UTemps</div>
    <div class="parameters">
      Salut ! Bienvenu sur le service qui va te permettre de gérer ton emploi du temps, de le modifier, de l\'exporter et encore plein d\'autres choses que je te laisse découvrir<br />
      <br />
      Pour rapidement t\'aider à te repérer: il y a plusieurs modes d\'affichage que tu peux choisir en cliquant en haut à droite<br />
      En haut à gauche, tu as le menu avec pleins d\'options et surtout la possibilité d\'exporter ton emploi du temps sur ton calendrier perso !<br />
      <br />
      N\'oublie pas que les modifications faites sont uniquement faites sur le site, par conséquent, il est impératif de prévenir les responsables UV d\'un changement. Vous en êtes les unique responsables<br />
      <br />
      N\'hésite pas à farfouiller le site et si tu rencontres un problème, prends un screen et signale le nous <a href="https://gitlab.utc.fr/simde/emploidutemps/issues">ici</a> ou <a href="mailto:simde@assos.utc.fr">par mail</a><br />
      Le service est encore tout neuf et subit encore des améliorations, mais est totalement utilisable<br />
      <br /><br />
      PS: N\'oubliez pas qu\'il y a une vie après les cours<br />
      <br />
      La bise,<br />
      Samy et le SiMDE
    </div>';
  }
  else
    echo '<div onClick="parameters()" style="cursor: pointer" id="popupHead">Cliquez ici pour retourner au menu</div>
    <div class="parameters" style="background-color: #FF0000">Erreur: impossible de trouver l\'information voulue</div>';
?>
