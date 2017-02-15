<?php include($_SERVER['DOCUMENT_ROOT'].'/ressources/php/include.php'); ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=0.75">
  <link rel="stylesheet" href="ressources/css/jquery.skeduler.css" type="text/css">
  <link rel="stylesheet" href="ressources/css/card.skeduler.css" type="text/css">
  <link rel="stylesheet" href="ressources/font-awesome/css/font-awesome.min.css">
  <script type="text/javascript" src="ressources/js/jquery-3.1.1.min.js"></script>
  <script type="text/javascript" src="ressources/js/interraction.js"></script>
  <script type="text/javascript" src="ressources/js/jquery.skeduler.js"></script>
  <script type="text/javascript" src="ressources/js/jquery.print.js"></script>
  <script type="text/javascript" src="ressources/js/jquery.touchSwipe.min.js"></script>
  <script type="text/javascript">
    function main () {
      selectMode('<?php echo (substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], "?") + 1) == '' ? '' : '&'), substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], "?") + 1), '\', \'', (isset($_GET['mode']) && is_string($_GET['mode']) && !empty($_GET['mode']) ? $_GET['mode'] : '');  ?>');

      //  Animer l'affichage d'une UV à échanger lors de la réception du mail
      <?php if (isset($_GET['id']) && is_string($_GET['id']) && !empty($_GET['id']))
        echo 'setTimeout(function () { $("#', $_GET['id'], '").click(); }, 100);';
      ?>

      $("body").keyup(function (event) {
        if(event.keyCode == 27)
            popupClose();
            window.search = '';
      });
    }

    $(window).resize(function() {
      setSkeduler();
    });

    $(function() {
      $('#skeduler-container').swipe( {
        swipeLeft: function() { setSkeduler(focusedDay + 1); },
        swipeRight: function() { setSkeduler(focusedDay - 1); }
      });
    });
  </script>
  <title>Emploi d'UTemps Beta 1.2</title>
</head>

<body onLoad='main()'>
  <div id='header'>
    <a id='title' href='/'>Emploi d'UTemps Beta 1.2</a>
    <div id='sTitle'></div>
    <div id='bar'></div>
  </div>
  <div id='zoneFocus' onClick='unFocus()'></div>
  <div id='zoneGrey'></div>

  <div id='zonePopup' onClick='popupClose();'></div>
  <div id='popup'></div>
  <div id='otherDay'><button onClick='setSkeduler(focusedDay - 1);'><i class="fa fa-arrow-left" aria-hidden="true"></i></button><button onClick='setSkeduler(focusedDay + 1);'><i class="fa fa-arrow-right" aria-hidden="true"></i></button></div>
  <div id="skeduler-container"></div>

  Futur exporter<button id='toPDF' onClick="$('#skeduler-container').print();"><img src='/ressources/img/pdf.png' alt='To PDF' /></button>
  <a style='color:#FFFFFF' href='https://moodle.utc.fr/login/index.php?authCAS=CAS' target="_blank">Moodle</a>
  <a style='color:#FFFFFF' href='https://assos.utc.fr/uvweb/' target="_blank">UVWeb</a>
  <a style='color:#FFFFFF' href='/maj.php'>Rechercher des mises à jour</a>
  <a style='color:#FFFFFF' href='/logs/changelog.txt'>Changelog</a>
  <?php $query = $GLOBALS['bdd']->prepare('SELECT desinscrit FROM etudiants WHERE login = ?');
    $GLOBALS['bdd']->execute($query, array($_SESSION['login']));

    if ($query->fetch()['desinscrit'] == '0')
      echo '<a style="color:#FFFFFF" onClick="desinscription();"">Se désinscrire du service (ne plus recevoir de demandes)</a>';
    else
      echo '<a style="color:#FFFFFF" onClick="reinscription();"">Se réinscrire du service (recevoir de nouveau des demandes)</a>';
  ?>
  <a style='color:#FFFFFF' href='/deconnexion.php'>Se déconnecter</a>
</body>

</html>
