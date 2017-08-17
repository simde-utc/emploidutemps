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
      generateCalendar([], 1, {});
      setCalendar();

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
          closePopup();
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

    // En cas d'erreur, recharger la page
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
  <div id='zonePopup' onClick='closePopup();'></div>
  <div id='popup'></div>

  <div id='title'></div>
  <div id='otherDay'><button onClick='setCalendar(focusedDay - 1);'><i class="fa fa-arrow-left" aria-hidden="true"></i></button><button onClick='setCalendar(focusedDay + 1);'><i class="fa fa-arrow-right" aria-hidden="true"></i></button></div>
  <div id="calendar-container"></div>

  <div id="nav">
    <div id='mode' class="menu">
      <div id='modeText'>
        Mode:
      </div>
      <input name='mode' id='mode_classique' type='radio' onClick='changeMode("classique");'><label for='mode_classique'> Classique</label><br />
      <input name='mode' id='mode_comparer' type='radio' onClick='changeMode("comparer");'><label for='mode_comparer'> Comparer</label><br />
      <input name='mode' id='mode_modifier' type='radio' onClick='changeMode("modifier");'><label for='mode_modifier'> Modifier</label><br />
      <input name='mode' id='mode_organiser' type='radio' onClick='changeMode("organiser");'><label for='mode_organiser'> Organiser</label><br />
      <input name='mode' id='mode_semaine' type='radio' onClick='changeMode("semaine");'><label id="mode_week" for='mode_semaine'> Semaine du <?php echo explode('-', $_SESSION['week'])[2], '/', explode('-', $_SESSION['week'])[1]; ?></label><br />
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
      <div id='affichage_tabs' class="sub-menu">
        Par emploi du temps:
        <div id='tabs'></div>
      </div>
      <div id='affichage_groups' class="sub-menu">
        Par groupe:
        <div id='groups'></div>
      </div>
    </div>
  </div>

  <div id="parameters" class="menu sub-menu">
    <div id='affichage_printed' class='menu sub-menu'>
      <div id='printedText'></div>
      <div id='printed'></div>
    </div>
    <div id='affichage_tools' class="menu sub-menu">
      Outils:
      <div style='display: none' id='weekTools'>
        <input type='radio' id='withWeekTool' name='weekTool' onClick="generate()" CHECKED><label for='withWeekTool'>Semaine</label>
        <input type='radio' id='withoutWeekTool' name='weekTool' onClick="generate()"><label for='withoutWeekTool'>Classique</label>
        <button id='eventTool' onClick="createEvenement()"><i class="fa fa-calendar-o" aria-hidden="true"></i> Créer un évènement</button>
      </div>
      <div id='printedTools'></div>
      <div id='tools'>
        <button id='export' onClick="exportDownload()"><i class="fa fa-download" aria-hidden="true"></i> Exporter/Télécharger</button>
        <button id='help' onClick="helpMePls()" DISABLED><i class="fa fa-question" aria-hidden="true"></i> Aide</button>
      </div>
    </div>
    <div id='affichage_useful' class="menu sub-menu">
      Liens utiles:
      <div>
        <button onClick="window.open('https://assos.utc.fr');"><i class="fa fa-external-link" aria-hidden="true"></i> Portail des assos</button>
        <button onClick="window.open('http://moodle.utc.fr/login/index.php?authCAS=CAS');"><i class="fa fa-external-link" aria-hidden="true"></i> Moodle</button>
        <button onClick="window.open('https://assos.utc.fr/uvweb/');"><i class="fa fa-external-link" aria-hidden="true"></i> UVWeb</button>
        <button onClick="window.open('https://gitlab.utc.fr/simde/emploidutemps');"><i class="fa fa-external-link" aria-hidden="true"></i> Gitlab (code source)</button>
        <button onClick="window.open('mailto:simde@assos.utc.fr?subject=[EmploidUTemps] ');"><i class="fa fa-envelope-o" aria-hidden="true"></i> Nous contacter</button>
      </div>
    </div>
    <div id='affichage_options' class="menu sub-menu">
      Options:
      <div>
        <button onClick="window.open('https://assos.utc.fr/');" DISABLED><i class="fa fa-thumbs-o-up" aria-hidden="true"></i> Se réhabonner</button>
        <button onClick="window.open('https://assos.utc.fr/');" DISABLED><i class="fa fa-thumbs-o-down" aria-hidden="true"></i> Se déshabonner</button>
        <button onClick="window.open('https://assos.utc.fr/');" DISABLED><i class="fa fa-remove" aria-hidden="true"></i> Se désinscrire définitivement</button>
      </div>
    </div>
    <div id='affichage_disconnect' class="menu sub-menu">
      <div>
        <button onClick="disconnect();"><i class="fa fa-sign-out" aria-hidden="true"></i> Se déconnecter</button>
      </div>
    </div>
    <div id='affichage_credit' class="menu sub-menu">
      <div style='text-align: center;'>
        <i class="fa fa-code" aria-hidden="true"></i> avec <i class="fa fa-heart" aria-hidden="true"></i> par Samy NASTUZZI
      </div>
    </div>
  </div>
</body>
</html>

<!--

        <button style="display: inline; margin-left: 0;" onClick="window.open(\'https://\' + window.location.hostname + \'/emploidutemps\' + \'/logs/changelog.txt\');"><i class="fa fa-file-text-o" aria-hidden="true"></i> Changelog</button>
        <button style="display: inline; margin-left: 5px;" onClick="parameters(\'checkUpdate\');"><i class="fa fa-refresh" aria-hidden="true"></i> Chercher une màj (indisponible pour le moment)</button>
      </div>
      <button onClick="parameters(\'contacter\');"><i class="fa fa-envelope-o" aria-hidden="true"></i> Nous contacter</button>';
//      <button onClick="window.open(\'https://\' + window.location.hostname + \'/emploidutemps\' + \'/maj.php\');">Rechercher des mises à jour</button>
        echo '<button onClick="parameters(\'sedesinscrire\');""><i class="fa fa-times" aria-hidden="true"></i> Se désinscrire du service</button>';
      else
        echo '<button onClick="parameters(\'reinscription\');""><i class="fa fa-check" aria-hidden="true"></i> Se réinscrire au service</button>';
    }

    </div>';
-->
