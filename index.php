<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps'.'/ressources/php/include.php'); ?>
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
      <?php
        $query = $GLOBALS['bdd']->prepare('SELECT login FROM etudiants WHERE login = ?');
        $GLOBALS['bdd']->execute($query, array($_SESSION['login']));

        if ($query->rowCount() == 0)
          $mode = 'organiser';
        else
          $mode = (isset($_GET['mode']) && is_string($_GET['mode']) && !empty($_GET['mode']) ? $_GET['mode'] : '');

        echo 'selectMode(\'', (substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], "?") + 1) == '' ? '' : '&'), substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], "?") + 1), '\', \'', $mode, "');";

        // Lance le paramètre demander (désinscription par exemple)
        if (isset($_GET['param']) && is_string($_GET['param']) && !empty($_GET['param']))
          echo 'setTimeout(function () { parameters("', $_GET['param'], '"); }, 1000);';
        //  Animer l'affichage d'une UV à échanger lors de la réception du mail
        else if (isset($_GET['id']) && is_string($_GET['id']) && !empty($_GET['id']))
          echo 'setTimeout(function () { $("#', $_GET['id'], '").click(); }, 1000);';
      ?>

      $("body").keyup(function (event) {
        if(event.keyCode == 27) {
          popupClose();
          window.search = '';
        }
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

    $(document).ajaxError(function() {
      location.reload();
    });
  </script>
  <title>Emploi d'UTemps</title>
</head>

<body onLoad='main()'>
  <div id='header'>
    <button id='parameters' onClick="parameters();"><i class="fa fa-2x fa-bars" aria-hidden="true"></i></button>
    <a id='title' href='/emploidutemps'>Emploi d'UTemps Beta 1.6</a>
    <div id='sTitle'></div>
    <div id='bar'></div>
  </div>
  <div id='zoneFocus' onClick='unFocus()'></div>
  <div id='zoneGrey'></div>

  <div id='zonePopup' onClick='popupClose();'></div>
  <div id='popup'></div>
  <div id='otherDay'><button onClick='setSkeduler(focusedDay - 1);'><i class="fa fa-arrow-left" aria-hidden="true"></i></button><button onClick='setSkeduler(focusedDay + 1);'><i class="fa fa-arrow-right" aria-hidden="true"></i></button></div>
  <div id="skeduler-container"></div>
</body>

</html>
