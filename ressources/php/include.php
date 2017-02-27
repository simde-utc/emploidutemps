<?php
  date_default_timezone_set('Europe/Paris');
  mb_internal_encoding("UTF-8");
  session_start();

  // ini_set('display_errors', 1);  ini_set('display_startup_errors', 1);  error_reporting(E_ALL);

  $etuPic = '<i class="searchImg fa fa-4x fa-user-o" style="padding-left:2px;" aria-hidden="true"></i>';
  $uvPic = '<i class="searchImg fa fa-4x fa-graduation-cap" style="padding-left:2px;" aria-hidden="true"></i>';
  $colors = array('#7DC779', '#82A1CA', '#F2D41F', '#457293', '#AB7AC6', '#DF6F53', '#B0CEE9', '#AAAAAA', '#576D7C', '#1C704E', '#F79565');
  $jours = array('lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche');
  include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/class/class.bdd.php');
  include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/class/class.curl.php');
  include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/class/class.cas.php');

  if (isset($_GET['MODCASID']) && is_string($_GET['MODCASID']))
    $_SESSION['MODCASID'] = $_GET['MODCASID'];

  $bdd = new BDD();
  $curl = new CURL(strpos($_SERVER['HTTP_HOST'],'utc') !== false);
  if (isset($_SESSION['MODCASID']))
    $curl->setCookies('MODCASID='.$_SESSION['MODCASID']);

  if (!isset($_SESSION['login'])) {
    if (!isset($_SESSION['_GET']))
      $_SESSION['_GET'] = $_GET;

  	$info = CAS::authenticate();

  	if ($info != -1) 	{
  		$_SESSION['login'] = $info['cas:user'];
      $_SESSION['mail'] = $info['cas:attributes']['cas:mail'];
      $_SESSION['prenom'] = $info['cas:attributes']['cas:givenName'];
      $_SESSION['nom'] = strtoupper($info['cas:attributes']['cas:sn']);
  		$_SESSION['ticket'] = $_GET['ticket'];
      $_SESSION['tab'] = array('uv' => array(), 'etu' => array());
      $_SESSION['etuActive'] = array();

      $query = $bdd->prepare('UPDATE etudiants SET nouveau = 0 WHERE login = ?');
      $bdd->execute($query, array($_SESSION['login']));

      $get = '?';
      foreach ($_SESSION['_GET'] as $key => $value) {
        if ($key != 'ticket')
          $get .= $key.'='.$value.'&';
      }

      header('Location: /emploidutemps/'.substr($get, 0, -1));
      exit;
  	}
  	else
      CAS::login();

  }

  function sendMail($mail, $subject, $message, $from = 'emploidutemps@assos.utc.fr') {
    $query = $GLOBALS['bdd']->prepare('SELECT desinscrit FROM etudiants WHERE mail = ?');
    $GLOBALS['bdd']->execute($query, array($mail));
    $data = $query->fetch();

    return FALSE;

    if ($data['desinscrit'] == '0')
      return mail($mail, $subject, $message.PHP_EOL.PHP_EOL.'Pour arrêter de recevoir des mails du service, tu peux à tout moment te désinscrire en cliquant ici: https://assos.utc.fr/emploidutemps/?param=sedesinscrire'.PHP_EOL.PHP_EOL.'En cas d\'erreur ou de bug, contacte-nous à cette adresse: simde@assos.utc.fr'.PHP_EOL.PHP_EOL.'Il y a une vie après les cours,'.PHP_EOL.'Le SiMDE', 'FROM:'.$from);

    return FALSE;
  }

  function isUpdating() {
    return file_exists($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/logs/'.'update') || file_exists($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/logs/'.'login');
  }

  function getARandomColor() {
    return $GLOBALS['colors'][mt_rand(1, count($GLOBALS['colors'])) - 1];
  }

  function getFgColor($bgColor) {
    if ((((hexdec(substr($bgColor, 1 , 2)) * 299) + (hexdec(substr($bgColor, 3 , 2)) * 587) + (hexdec(substr($bgColor, 5 , 2)) * 114))) > 127000)
      return '#000000';
    else
      return '#FFFFFF';
  }
/*
  function notification($title, $text) {
    // Faire la notif'
  }
*/
  function printEtu($etu) {
    if ($etu['mail'] == NULL) {
      $mail = $etu['login'].'@etu.utc.fr';
      $name = $etu['login'];
    }
    else {
      $mail = $etu['mail'];
      $name = $etu['nom'].' '.$etu['prenom'];
    }

    echo '<div class="searchCard" onClick="edtEtu(\'', $etu['login'], '\')">';

    if (file_exists($pic))
      echo '<img class="searchImg" src="https://'.$_SERVER['SERVER_NAME'].'/pic/'.$etu['login'].'.jpg" alt="photo"/>';
    else
      echo $GLOBALS['etuPic'];

    echo '<div>
        <div>', $name, '</div>
        <div>', $etu['semestre'], '</div>
        <div>', $mail, '</div>
      </div>
    </div>';
  }

  function printUV($uv) {
    echo '<div class="searchCard" onClick="edtUV(\'', $uv['uv'], '\')">';

    if (file_exists($pic))
      echo '<img class="searchImg" src="https://'.$_SERVER['SERVER_NAME'].'/pic/'.$etu['login'].'.jpg" alt="photo"/>';
    else
      echo $GLOBALS['uvPic'];

    echo '<div>
        <div>', $uv['uv'], '</div>
      </div>
    </div>';
  }

  function printSelf($etu) {
    $pic = $_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/pic/'.$_SESSION['login'].'.jpg';

    if (!file_exists($pic))
      $pic = 'https://'.$_SERVER['SERVER_NAME'].'/pic/'.$_SESSION['login'].'.jpg';
    else
      $pic = $GLOBALS['voidPic'];

    echo '<div class="searchCard" style="width: 100%" onClick="popupClose(); window.login = \'\'; window.uv = \'\'; selectMode("", window.mode);">';

    if (file_exists($pic))
      echo '<img class="searchImg" src="https://'.$_SERVER['SERVER_NAME'].'/pic/'.$etu['login'].'.jpg" alt="photo"/>';
    else
      echo $GLOBALS['voidPic'];

    echo '<div>
        <div>', $_SESSION['nom'], ' ', $_SESSION['prenom'], '</div>
        <div>', $etu['semestre'], '</div>
        <div>', $_SESSION['mail'], '</div>
      </div>
    </div>';
  }

  function printEtuList($idUV, $edt = NULL) {
    $etus = getEtuFromIdUV($idUV);
    $uv = getUVFromIdUV($idUV);

    echo '<div id="popupHead">Liste des ', $uv['nbrEtu'], ' étudiants en ', ($uv['type'] == 'D' ? $uv['type'] = 'TD' : ($uv['type'] == 'C' ? $uv['type'] = 'cours' : $uv['type'] = 'TP')), ' de ', $uv['uv'], ' chaque ', $GLOBALS['jours'][$uv['jour']],' de ', $uv['debut'], ' à ', $uv['fin'], ($uv['semaine'] == '' ? '' : ' chaque semaine '.$uv['semaine']), '</div><div id="searchResult">';

    if ($edt != NULL) {
      $where = array_search($edt, $etus);
      if ($where != FALSE) {
        printSelf($etus[$where]);
        unset($etus[$where]);
      }
    }

    foreach ($etus as $etu)
      printEtu($etu);

    echo '</div>';
  }

  function printEtuAndUVList($search, $limit = NULL, $begin = 0) {
    $etus = getEtuListFromSearch($search);
    $uvs = getUVListFromSearch($search);

    if (empty($etus) && empty($uvs) && !empty($search)) {
      echo '<div class="searchCard" style="background-color: #FF0000; color: #FFF; margin: 5px; padding: 5px; height: 100%; width: 100%; text-align: center; display: block;">Aucun résultat trouvé</div>';
      exit;
    }

    $sessionInfo = getEtu($_SESSION['login']);

    if (in_array($sessionInfo, $etus))
      unset($etus[array_search($sessionInfo, $etus)]);

    if ($limit == NULL) {
      foreach ($etus as $etu)
        printEtu($etu);

      foreach ($uvs as $uv)
        printUV($uv);
    }
    else {
      $i = 0;

      if ($begin != 0)
        echo '<div class="searchCard" style="background-color: #0000FF; color: #FFF; padding: 5px; height: 100%; width: 100%; text-align: center; display: block;" onClick="window.search=\'\'; printEtuAndUVList(', $begin - $limit, ');">Cliquez ici pour afficher les résultats précédents</div>';

      foreach ($etus as $etu) {
        if ($i++ < $begin)
          continue;
        else if ($i > $limit + $begin)
          break;

        printEtu($etu);
      }

      foreach ($uvs as $uv) {
        if ($i++ < $begin)
          continue;
        else if ($i > $limit + $begin)
          break;

        printUV($uv);
      }

      if ($i > $limit + $begin)
        echo '<div class="searchCard" style="background-color: #0000FF; color: #FFF; padding: 5px; height: 100%; width: 100%; text-align: center; display: block;" onClick="window.search=\'\'; printEtuAndUVList(', $begin + $limit, ');">Cliquez ici pour afficher la suite de la recherche</div>';
    }
  }

  function getRecuesList($login = NULL, $idExchange = NULL, $disponible = NULL, $echange = NULL, $idUV = NULL, $for = NULL, $date = NULL) {
    $query = $GLOBALS['bdd']->prepare('SELECT recues.idEchange, echanges.idUV, echanges.pour, recues.date, recues.disponible, recues.echange, echanges.active FROM recues, echanges WHERE (? IS NULL OR recues.login = ?) AND (? IS NULL OR echanges.idUV = ?) AND (? IS NULL OR echanges.pour = ?) AND (? IS NULL OR recues.idEchange = ?) AND (? IS NULL OR recues.disponible = ?) AND (? IS NULL OR recues.echange = ?) AND (? IS NULL OR recues.date = ?) AND echanges.idEchange = recues.idEchange');
    $GLOBALS['bdd']->execute($query, array($login, $login, $idUV, $idUV, $for, $for, $idExchange, $idExchange, $disponible, $disponible, $echange, $echange, $date, $date));

    return $query->fetchAll();
  }

  function getEchange($idUV, $pour, $active = NULL) {
    $query = $GLOBALS['bdd']->prepare('SELECT idEchange, active FROM echanges WHERE idUV = ? AND pour = ? AND (? IS NULL OR active = ?)');
    $GLOBALS['bdd']->execute($query, array($idUV, $pour, $active, $active));

    return $query->fetchAll();
  }

  function getEnvoiesList($login = NULL, $idExchange = NULL, $disponible = NULL, $echange = NULL, $idUV = NULL, $for = NULL, $date = NULL) {
    $query = $GLOBALS['bdd']->prepare('SELECT login, envoies.idEchange, idUV, pour, date, note, envoies.disponible, envoies.echange FROM echanges, envoies WHERE (? IS NULL OR echanges.idEchange = ?) AND (? IS NULL OR envoies.login = ?) AND (? IS NULL OR envoies.disponible = ?) AND (? IS NULL OR envoies.echange = ?) AND (? IS NULL OR echanges.idUV = ?) AND (? IS NULL OR echanges.pour = ?) AND (? IS NULL OR envoies.date = ?) AND echanges.idEchange = envoies.idEchange ORDER BY date');
    $GLOBALS['bdd']->execute($query, array($idExchange, $idExchange, $login, $login, $disponible, $disponible, $echange, $echange, $idUV, $idUV, $for, $for, $date, $date));

    return $query->fetchAll();
  }

  function getEtuListFromSearch($search) {
    $query = $GLOBALS['bdd']->prepare('SELECT login, semestre, mail, prenom, nom FROM etudiants WHERE lower(login) LIKE lower(CONCAT("%", ?, "%")) OR lower(CONCAT(prenom, "_", nom, "_", prenom)) LIKE lower(CONCAT("%", ?, "%")) ORDER BY nom, prenom, login');
    $GLOBALS['bdd']->execute($query, array($search, $search));

    return $query->fetchAll();
  }

  function getUVListFromSearch($search) { // Plus rapide pour la recherche (et puis chaque UV est unique dans couleurs)
    $query = $GLOBALS['bdd']->prepare('SELECT uv FROM couleurs WHERE lower(uv) LIKE lower(CONCAT("%", ?, "%"))');
    $GLOBALS['bdd']->execute($query, array($search));

    return $query->fetchAll();
  }

  function getEtuFromIdUV($idUV, $desinscrit = NULL, $actuel = 1) {
    $query = $GLOBALS['bdd']->prepare('SELECT etudiants.login, etudiants.semestre, etudiants.mail, etudiants.prenom, etudiants.nom, etudiants.nouveau, etudiants.desinscrit, cours.actuel, cours.echange FROM etudiants, cours WHERE cours.id = ? AND cours.actuel = ? AND (? IS NULL OR desinscrit = ?) AND etudiants.login = cours.login ORDER BY login');
    $GLOBALS['bdd']->execute($query, array($idUV, $actuel, $desinscrit, $desinscrit));

    return $query->fetchAll();
  }

  function getEtu($login) {
    $query = $GLOBALS['bdd']->prepare('SELECT login, semestre, mail, prenom, nom, uvs FROM etudiants WHERE login = ?');
    $GLOBALS['bdd']->execute($query, array($login));

    return $query->fetch();
  }

  function getEdtEtu($login, $actuel = 1, $echange = NULL) {
    $query = $GLOBALS['bdd']->prepare('SELECT uvs.id, uvs.uv, uvs.type, uvs.groupe, uvs.jour, uvs.debut, uvs.fin, uvs.salle, uvs.frequence, uvs.semaine, cours.color, couleurs.color AS colorUV FROM uvs, cours, couleurs WHERE cours.login = ? AND cours.actuel = ? AND (? IS NULL OR cours.echange = ?) AND uvs.uv = couleurs.uv AND uvs.id=cours.id ORDER BY uvs.jour, uvs.debut, semaine, groupe');
    $GLOBALS['bdd']->execute($query, array($login, $actuel, $echange, $echange));

    return $query->fetchAll();
  }

  function getEdtUV($uv, $type = NULL) {
    $query = $GLOBALS['bdd']->prepare('SELECT id, uvs.uv, type, groupe, jour, debut, fin, salle, frequence, semaine, nbrEtu, color FROM uvs, couleurs WHERE uvs.uv = couleurs.uv AND uvs.uv = ? AND (? IS NULL OR type = ?) ORDER BY uv, jour, debut, semaine, groupe');
    $GLOBALS['bdd']->execute($query, array($uv, $type, $type));

    return $query->fetchAll();
  }

  function getUVFromIdUV($idUV) {
    $query = $GLOBALS['bdd']->prepare('SELECT uv, type, jour, debut, fin, salle, groupe, frequence, semaine, nbrEtu FROM uvs WHERE uvs.id = ?');
    $GLOBALS['bdd']->execute($query, array($idUV));

    return $query->fetch();
  }

  function isEdtEtuVoid($login, $actuel = 1, $echange = NULL) {
    return getEdtEtu($login, $actuel, $echange) == array();
  }

  function isUV($uv) { // Ici on utilise couleurs pour accélérer la recherche
    $query = $GLOBALS['bdd']->prepare('SELECT uv FROM couleurs WHERE uv = ?');
    $GLOBALS['bdd']->execute($query, array($uv));

    return $query->rowCount() == 1;
  }

  function isEtu($login) {
    $query = $GLOBALS['bdd']->prepare('SELECT login FROM etudiants WHERE login = ?');
    $GLOBALS['bdd']->execute($query, array($login));

    return $query->rowCount() == 1;
  }

  function cancelIdExchange($idExchange, $login = NULL) {
    $query = $GLOBALS['bdd']->prepare('DELETE FROM envoies WHERE idEchange = ? AND login = ?');
    $GLOBALS['bdd']->execute($query, array($idExchange, $login == NULL ? $_SESSION['login'] : $login));

    // Si on était le seul à demander, on désactive l'annonce
    if (count(getEnvoiesList(NULL, $idExchange, 1)) == 0) {
      $query = $GLOBALS['bdd']->prepare('UPDATE echanges SET active = 0 WHERE idEchange = ?');
      $GLOBALS['bdd']->execute($query, array($idExchange));
    }
  }

  function refuseIdExchange($idExchange) {
    $query = $GLOBALS['bdd']->prepare('UPDATE recues SET disponible = 0, date = NOW() WHERE login = ? AND idEchange = ?');
    $GLOBALS['bdd']->execute($query, array($_SESSION['login'], $idExchange));

    if (count(getRecuesList(NULL, $idExchange, 1)) == 0) { // On regarde s'il reste encore des propositions non répondus
      // On annonce que personne n'a accepté la proposition
      $query = $GLOBALS['bdd']->prepare('UPDATE echanges SET active = 0 WHERE idEchange = ?');
      $GLOBALS['bdd']->execute($query, array($idExchange));
      // On indique à tous les demandeurs que tout le monde a refusé
      $query = $GLOBALS['bdd']->prepare('UPDATE envoies SET disponible = 0, date = NOW() WHERE idEchange = ? AND disponible = 1');
      $GLOBALS['bdd']->execute($query, array($idExchange));

      $envoies = getEnvoiesList(NULL, $idExchange, 1);
      foreach ($envoies as $envoie) {
        $infosLogin = getEtu($envoie['login']);
        mail($infosLogin['login'], 'Echange refusé', 'Salut !'.PHP_EOL.'Une demande d\'échange a été refusée par tout le monde.'.PHP_EOL.'Tente ta chance avec une autre proposition!', 'From: agendutc@nastuzzi.fr');
      }
    }
  }

  if (isUpdating()) {
    echo 'Emploi d\'UTemps est en cours de mise à jour, veuillez patienter. La page se relancera d\'elle-même lorsque la mise à jour sera terminée
    <script>
    setTimeout(function(){ window.reload(); }, 5000);
    </script>';
    exit;
  }
?>
