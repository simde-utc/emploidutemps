<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps'.'/ressources/php/include.php'); ?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=0.75">
  <link rel="stylesheet" href="ressources/css/style.css" type="text/css">
  <link rel="stylesheet" href="ressources/css/font-awesome/css/font-awesome.min.css">
  <script type="text/javascript" src="ressources/js/jquery-3.1.1.min.js"></script>
  <script type="text/javascript" src="ressources/js/interraction.js"></script>
  <script type="text/javascript" src="ressources/js/generation.js"></script>
  <script type="text/javascript" src="ressources/js/jquery.touchSwipe.min.js"></script>
  <script type="text/javascript" src="ressources/js/html2canvas.min.js"></script>
  <script type="text/javascript" src="ressources/js/jspdf.min.js"></script>
  <script type="text/javascript">
    function main () {
      window.get = <?php
        $query = $GLOBALS['db']->request(
          'SELECT login FROM students WHERE login = ?',
          array($_SESSION['login'])
        );

        if ($query->rowCount() == 0)
          echo json_encode(array('mode' => 'semaine'));
        else
          echo json_encode($_GET);

        echo ';
        ';

        // Lance le paramètre demanér (désinscription par exemple)
        if (isset($_GET['param']) && is_string($_GET['param']) && !empty($_GET['param']))
          echo 'setTimeout(function () { parameters("', $_GET['param'], '"); }, 1000);';

        $query = $GLOBALS['db']->request(
          'SELECT status FROM students WHERE login = ?',
          array($_SESSION['login'])
        );
        $data = $query->fetch();

    /*    if ($data['status'] == '0') {
          $query = $db->prepare('UPDATE students SET status = 0 WHERE login = ?');
          $db->execute($query, array($_SESSION['login']));
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

    var toogleNav = function () {
      $('#nav').toggleClass('see');
    }

    var toogleParam = function () {
      $('#parameters').toggleClass('see');
    }

    $(window).resize(function() {
      setCalendar();
    });

    $(function () {
      $('#calendar-container').swipe( {
        swipeLeft: function() { setCalendar(focusedDay + 1); },
        swipeRight: function() { setCalendar(focusedDay - 1); }
      });
    });

    $(document).ajaxError(function(err) {
      console.log(err)
      //location.reload();
    });
  </script>
  <title>Emploi d'UTemps</title>
</head>

<body onLoad='main()'>
  <div id='header'>
    <button id='navButton' onClick='toogleNav()'><i class="fa fa-2x fa-bars" aria-hidden="true"></i></button>
    <a id='name' href='/emploidutemps'>Emploi d'UTemps</a>
    <button id='search' onClick='search()'><i class="fa fa-2x fa-search" aria-hidden="true"></i></button>
    <button id='parametersButton' onClick="toogleParam()"><i class="fa fa-2x fa-cog" aria-hidden="true"></i></button>
  </div>

  <div id='zoneFocus' onClick='unFocus()'></div>
  <div id='zoneGrey'></div>
  <div id='zonePopup' onClick='popupClose();'></div>
  <div id='popup'></div>

  <div id="main">
    <div id='title'></div>
    <div id='otherDay'><button onClick='setCalendar(focusedDay - 1);'><i class="fa fa-arrow-left" aria-hidden="true"></i></button><button onClick='setCalendar(focusedDay + 1);'><i class="fa fa-arrow-right" aria-hidden="true"></i></button></div>
    <div id="calendar-container"></div>
  </div>

  <div id="nav">
    <div id='mode' class="menu">
      <div id='modeText'>
        Mode:
      </div>
      <input name='mode' id='mode_classique' type='radio' onClick='window.get.mode="classique"; generate();'><label for='mode_classique'> Classique</label><br />
      <input name='mode' id='mode_comparer' type='radio' onClick='window.get.mode="comparer"; generate();'><label for='mode_comparer'> Comparer</label><br />
      <input name='mode' id='mode_modifier' type='radio' onClick='window.get.mode="modifier"; generate();'><label for='mode_modifier'> Modifier</label><br />
      <input name='mode' id='mode_organiser' type='radio' onClick='window.get.mode="organiser"; generate();'><label for='mode_organiser'> Organiser</label><br />
      <input name='mode' id='mode_semaine' type='radio' onClick='window.get.mode="semaine"; generate();'><label id="mode_week" for='mode_semaine'> Semaine du <?php echo explode('-', $_SESSION['week'])[2], '/', explode('-', $_SESSION['week'])[1]; ?></label><br />
      <div id='week' class="sub-menu">
        <button id='before'><i class="fa fa-arrow-left" aria-hidden="true"></i></button>
        <button id='actual'>Cette semaine</button>
        <button id='after'><i class="fa fa-arrow-right" aria-hidden="true"></i></button>
      </div>
    </div>
    <div class="menu">
      <div id='affichageText'>
        Affichage:
      </div>
      <div id='affichage_tabs' class="sub-menu">Par emploi du temps:</div>
      <div id='tabs' class='sub-menu'></div>
      <div id='affichage_groups' class="sub-menu">Par groupe:</div>
      <div id='groups' class='sub-menu'></div>
    </div>
  </div>

  <div id="parameters">
    <div id='nbr_printed' class='menu sub-menu'>0 Affiché</div>
    <div id='printed' class='menu sub-menu'></div>
    <div id='tools' class="menu sub-menu">
      <div id='toolsText'>
        Outils:
      </div>
      <div>
        <button id='export' onClick="exportDownload()"><i class="fa fa-download" aria-hidden="true"></i> Exporter/Télécharger</button>
      </div>
    </div>
  </div>
</body>

</html>
