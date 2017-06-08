<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps'.'/ressources/php/include.php'); ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=0.75">
  <link rel="stylesheet" href="ressources/css/style.css" type="text/css">
  <link rel="stylesheet" href="ressources/font-awesome/css/font-awesome.min.css">
  <script type="text/javascript" src="ressources/js/jquery-3.1.1.min.js"></script>
  <script type="text/javascript" src="ressources/js/interraction.js"></script>
  <script type="text/javascript" src="ressources/js/generation.js"></script>
  <script type="text/javascript" src="ressources/js/jquery.touchSwipe.min.js"></script>
  <script type="text/javascript" src="ressources/js/jsPDF.js"></script>
  <script type="text/javascript">
    function main () {
      window.get = <?php
        $query = $GLOBALS['bdd']->prepare('SELECT login FROM students WHERE login = ?');
        $GLOBALS['bdd']->execute($query, array($_SESSION['login']));

        if ($query->rowCount() == 0)
          echo json_encode(array('mode' => 'semaine'));
        else
          echo json_encode($_GET);

        echo ';
      ';

        // Lance le paramètre demanér (désinscription par exemple)
        if (isset($_GET['param']) && is_string($_GET['param']) && !empty($_GET['param']))
          echo 'setTimeout(function () { parameters("', $_GET['param'], '"); }, 1000);';
        //  Animer l'affichage d'une UV à échanger lors de la réception du mail
        else if (isset($_GET['id']) && is_string($_GET['id']) && !empty($_GET['id']))
          echo 'setTimeout(function () { $("#', $_GET['id'], '").click(); }, 1000);';

        $query = $GLOBALS['bdd']->prepare('SELECT status FROM students WHERE login = ?');
        $GLOBALS['bdd']->execute($query, array($_SESSION['login']));
        $data = $query->fetch();

    /*    if ($data['status'] == '0') {
          $query = $bdd->prepare('UPDATE students SET status = 0 WHERE login = ?');
          $bdd->execute($query, array($_SESSION['login']));
          echo 'setTimeout(function () { parameters("nouveau"); }, 1500);';
        }*/
      ?>

      $("body").keyup(function (event) {
        if(event.keyCode == 27) {
          popupClose();
          window.search = '';
        }
      });

      generate();
    }

    $(window).resize(function() {
      setCalendar();
    });

    $(function() {
      $('#calendar-container').swipe( {
        swipeLeft: function() { setCalendar(focusedDay + 1); },
        swipeRight: function() { setCalendar(focusedDay - 1); }
      });
    });
/*
    $(document).ajaxError(function() {
      location.reload();
    });*/
  </script>
  <title>Emploi d'UTemps</title>
</head>

<body onLoad='main()'>
  <div id='header'>
    <button id='parameters' onClick="parameters();"><i class="fa fa-2x fa-bars" aria-hidden="true"></i></button>
    <a id='title' href='/emploidutemps'>Emploi d'UTemps</a>
    <div id='sTitle'></div>
    <div id='bar'>
      <div id='tabs'></div>
      <div id='option'>
        <button id='search' onClick='searchTab()'><i class="fa fa-search" aria-hidden="true"></i></button>
        <select id='mode' onChange='changeMode(this.options[this.selectedIndex].value)'>
          <option value="classique">Classique</option>
          <option value="semaine">Semaine</option>
          <option value="comparer">Comparer</option>
          <option value="modifier">Modifier</option>
        </select>
      </div>
    </div>
  </div>
  <div id='zoneFocus' onClick='unFocus()'></div>
  <div id='zoneGrey'></div>

  <div id='zonePopup' onClick='popupClose();'></div>
  <div id='popup'></div>
  <div id='otherDay'><button onClick='setcalendar(focusedDay - 1);'><i class="fa fa-arrow-left" aria-hidden="true"></i></button><button onClick='setcalendar(focusedDay + 1);'><i class="fa fa-arrow-right" aria-hidden="true"></i></button></div>
  <div id="calendar-container"></div>
</body>

</html>
