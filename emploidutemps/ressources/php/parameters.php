<?php
  echo '<div id="popupHead">Paramètres</div>
  Futur exporter<button id="toPDF" onClick="$(\'#skeduler-container\').print();"><img src=\'/ressources/img/pdf.png\' alt=\'To PDF\' /></button>
  <a style=\'color:#FFFFFF\' href=\'https://moodle.utc.fr/login/index.php?authCAS=CAS\' target="_blank">Moodle</a>
  <a style=\'color:#FFFFFF\' href=\'https://assos.utc.fr/uvweb/\' target="_blank">UVWeb</a>
  <a style=\'color:#FFFFFF\' href=\'/maj.php\'>Rechercher des mises à jour</a>
  <a style=\'color:#FFFFFF\' href=\'/logs/changelog.txt\'>Changelog</a>';

  $query = $GLOBALS['bdd']->prepare('SELECT desinscrit FROM etudiants WHERE login = ?');
  $GLOBALS['bdd']->execute($query, array($_SESSION['login']));

  if ($query->fetch()['desinscrit'] == '0')
    echo '<a style="color:#FFFFFF" onClick="desinscription();"">Se désinscrire du service (ne plus recevoir de demandes)</a>';
  else
    echo '<a style="color:#FFFFFF" onClick="reinscription();"">Se réinscrire du service (recevoir de nouveau des demandes)</a>';

  echo '<a style=\'color:#FFFFFF\' href=\'/deconnexion.php\'>Se déconnecter</a>';
?>
