<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/include.php');
  if (isset($_GET['mode']) && is_string($_GET['mode']) && !empty($_GET['mode']))
    $mode = $_GET['mode'];
  else
    exit;

  if (isset($_GET['addTab']) && is_string($_GET['addTab']) && !empty($_GET['addTab'])) {
    if (isUV($_GET['addTab'])) {
      if (!in_array($_GET['addTab'], $_SESSION['tab']['uv']) && $_SESSION['login'] != $_GET['addTab'])
        array_push($_SESSION['tab']['uv'], $_GET['addTab']);
    }
    elseif (isEtu($_GET['addTab'])) {
      if (!in_array($_GET['addTab'], $_SESSION['tab']['etu']) && $_SESSION['login'] != $_GET['addTab']) {
        array_push($_SESSION['tab']['etu'], $_GET['addTab']);
        array_push($_SESSION['etuActive'], $_GET['addTab']);
      }
    }
  }
  elseif (isset($_GET['delTab']) && is_string($_GET['delTab']) && !empty($_GET['delTab'])) {
    if (in_array($_GET['delTab'], $_SESSION['tab']['uv']))
      unset($_SESSION['tab']['uv'][array_search($_GET['delTab'], $_SESSION['tab']['uv'])]);
    elseif (in_array($_GET['delTab'], $_SESSION['tab']['etu'])) {
      unset($_SESSION['tab']['etu'][array_search($_GET['delTab'], $_SESSION['tab']['etu'])]);

      if (in_array($_GET['delTab'], $_SESSION['etuActive']))
        unset($_SESSION['etuActive'][array_search($_GET['delTab'], $_SESSION['etuActive'])]);
    }
  }
  elseif (isset($_GET['addEtuActive']) && is_string($_GET['addEtuActive']) && !empty($_GET['addEtuActive'])) {
    if (!in_array($_GET['addEtuActive'], $_SESSION['etuActive']))
      array_push($_SESSION['etuActive'], $_GET['addEtuActive']);
  }
  elseif (isset($_GET['delEtuActive']) && is_string($_GET['delEtuActive']) && !empty($_GET['delEtuActive'])) {
    if (in_array($_GET['delEtuActive'], $_SESSION['etuActive']))
      unset($_SESSION['etuActive'][array_search($_GET['delEtuActive'], $_SESSION['etuActive'])]);
  }

  echo '<div id="menu">';

  if ($mode == 'planifier' || $mode == 'organiser') {
    $date = new DateTime($_SESSION['week']);
    $date->modify('-7 day');
    echo '<button ', (isAGoodDate($date->format('Y-m-d')) ? 'class="notActive" onClick="planifier(\'\', \''.$date->format('Y-m-d').'\')"' : 'style="cursor: default;  background-color: #555555;" disabled'), '><i class="fa fa-arrow-left" aria-hidden="true"></i></button>';
    $date->modify('7 day');
    echo '<button class="notActive" onClick="planifier(\'\', \'', date('Y-m-d', strtotime('monday this week')), '\')">Sem. ', $date->format('d/m'), '</button>';
    $date->modify('7 day');
    echo '<button ', (isAGoodDate($date->format('Y-m-d')) ? 'class="notActive" onClick="planifier(\'\', \''.$date->format('Y-m-d').'\')"' : 'style="cursor: default;  background-color: #555555;" disabled'), '><i class="fa fa-arrow-right" aria-hidden="true"></i></button>';
  }

  $nbrRecu = count(getRecuesList($_SESSION['login'], NULL));
  $nbrRecuNouveau = count(getRecuesList($_SESSION['login'], NULL, 1, 0));
  $nbrRecuAccepte = count(getRecuesList($_SESSION['login'], NULL, 0, 1)) + count(getRecuesList($_SESSION['login'], NULL, 1, 1));
  $nbrRecuRefuse = count(getRecuesList($_SESSION['login'], NULL, 0, 0));
  $nbrEnvoi = count(getEnvoiesList($_SESSION['login'], NULL));
  $nbrEnvoiNouveau = count(getEnvoiesList($_SESSION['login'], NULL, 1, 0));
  $nbrEnvoiAccepte = count(getEnvoiesList($_SESSION['login'], NULL, 0, 1)) + count(getEnvoiesList($_SESSION['login'], NULL, 1, 1));
  $nbrEnvoiRefuse = count(getEnvoiesList($_SESSION['login'], NULL, 0, 0));
  $nbrAnnule = count(getRecuesList($_SESSION['login'], NULL, 1, 1)) + count(getEnvoiesList($_SESSION['login'], NULL, 1, 1)) + count(getAnnulationList($_SESSION['login']));

  echo '<button id="sessionLogin" class=', (($mode != 'organiser' && (($mode != 'modifier' && (!isset($_GET['uv']) || $_GET['uv'] == '')) || ($mode == 'modifier' && (!isset($_GET['envoi']) || $nbrEnvoi == 0) && (!isset($_GET['recu']) || $nbrRecu == 0) && (!isset($_GET['annule']) || $nbrAnnule == 0) && !isset($_GET['original']) && !isset($_GET['changement'])) || $_GET == array('mode' => 'modifier', 'login' => '', 'addTab' => '')) && (!isset($_GET['login']) || isset($_GET['login']) && is_string($_GET['login']) && (($_GET['login'] == $_SESSION['login']) || ($_GET['login'] == '')) || empty($_GET) || (isset($_GET['tab']) && $_GET['tab'] == 1)) && ($mode != 'planifier' || !((isset($_GET['cours']) && $_GET['cours'] == 1) && !(isset($_GET['event']) && $_GET['event'] == 1)) && !(isset($_GET['reu']) && $_GET['reu'] == 1) && !(isset($_GET['salle']) && is_string($_GET['salle']) && !empty($_GET['salle'])))) ? '\'active\'>' : '\'notActive\' onClick="edtEtu(\'\');">');
  echo $_SESSION['nom'], ' ', $_SESSION['prenom'], '</button>';

  if ($mode == 'planifier') {
      echo '<div style="border: 1px SOLID #000000;"></div>
      <button class="', (isset($_GET['cours']) && $_GET['cours'] == '1' ? 'active"' : 'notActive" onClick="planifier(\'cours=1\')"'), '>Cours</button>
      <button class="notActive" onClick="planifier(\'event=1\')" style="cursor: default; background-color: #555555;" disabled>Evènements</button>
      <button class="notActive" onClick="planifier(\'reu=1\')" style="cursor: default;  background-color: #555555;" disabled>Réunions</button>
      <div style="border: 1px SOLID #000000;"></div>
      <button class="', (isset($_GET['salle']) && $_GET['salle'] == '1' ? 'active"' : 'notActive" onClick="planifier(\'salle=1\')"'), '>Salles libres 1-2h</button>
      <button class="', (isset($_GET['salle']) && $_GET['salle'] == '3' ? 'active"' : 'notActive" onClick="planifier(\'salle=3\')"'), '>3-4h</button>
      <button class="', (isset($_GET['salle']) && $_GET['salle'] == '5' ? 'active"' : 'notActive" onClick="planifier(\'salle=5\')"'), '>5-6h</button>
      <button class="', (isset($_GET['salle']) && $_GET['salle'] == '7' ? 'active"' : 'notActive" onClick="planifier(\'salle=7\')"'), '>7-8h</button>
      <button class="', (isset($_GET['salle']) && $_GET['salle'] == '-8' ? 'active"' : 'notActive" onClick="planifier(\'salle=-8\')"'), '>+ 8h</button>';
  }
  elseif ($mode == 'modifier') {
    if ($nbrRecu != 0)
      echo '<div style="border: 1px SOLID #000000;"></div>
      <button class="', (isset($_GET['recu']) && $_GET['recu'] == '1' ? 'active"' : 'notActive" onClick="seeExchanges(\'recu\', 1);"'), '>', $nbrRecu, ' reçu', ($nbrRecu > 1 ? 's': ''), ':</button>
      <button class="', (isset($_GET['recu']) && $_GET['recu'] == 'nouveau' ? 'active"' : 'notActive" onClick="seeExchanges(\'recu\', \'nouveau\');"'), ' style="color: #7777FF;',($nbrRecuNouveau == 0 ? ' cursor: default;  background-color: #555555;" disabled' : '"'), '><i class="fa fa-question" aria-hidden="true"></i></button>
      <button class="', (isset($_GET['recu']) && $_GET['recu'] == 'accepte' ? 'active"' : 'notActive" onClick="seeExchanges(\'recu\', \'accepte\');"'), ' style="color: #00FF00;',($nbrRecuAccepte == 0 ? ' cursor: default;  background-color: #555555;" disabled' : '"'), '><i class="fa fa-check" aria-hidden="true"></i></button>
      <button class="', (isset($_GET['recu']) && $_GET['recu'] == 'refuse' ? 'active"' : 'notActive" onClick="seeExchanges(\'recu\', \'refuse\');"'), ' style="color: #FF0000;',($nbrRecuRefuse == 0 ? ' cursor: default;  background-color: #555555;" disabled' : '"'), '><i class="fa fa-times" aria-hidden="true"></i></button>';
    if ($nbrEnvoi != 0)
      echo '<div style="border: 1px SOLID #000000;"></div>
      <button class="', (isset($_GET['envoi']) && $_GET['envoi'] == '1' ? 'active"' : 'notActive" onClick="seeExchanges(\'envoi\', 1);"'), '>', $nbrEnvoi, ' envoi', ($nbrEnvoi > 1 ? 's': ''), ':</button>
      <button class="', (isset($_GET['envoi']) && $_GET['envoi'] == 'nouveau' ? 'active"' : 'notActive" onClick="seeExchanges(\'envoi\', \'nouveau\');"'), ' style="color: #7777FF;',($nbrEnvoiNouveau == 0 ? ' cursor: default;  background-color: #555555;" disabled' : '"'), '>?</button>
      <button class="', (isset($_GET['envoi']) && $_GET['envoi'] == 'accepte' ? 'active"' : 'notActive" onClick="seeExchanges(\'envoi\', \'accepte\');"'), ' style="color: #00FF00;',($nbrEnvoiAccepte == 0 ? ' cursor: default;  background-color: #555555;" disabled' : '"'), '><i class="fa fa-check" aria-hidden="true"></i></button>
      <button class="', (isset($_GET['envoi']) && $_GET['envoi'] == 'refuse' ? 'active"' : 'notActive" onClick="seeExchanges(\'envoi\', \'refuse\');"'), ' style="color: #FF0000;',($nbrEnvoiRefuse == 0 ? ' cursor: default;  background-color: #555555;" disabled' : '"'), '><i class="fa fa-times" aria-hidden="true"></i></button>';
    if ($nbrAnnule != 0)
      echo '<div style="border: 1px SOLID #000000;"></div>
      <button class="', (isset($_GET['annule']) && $_GET['annule'] == '1' ? 'active"' : 'notActive" onClick="seeExchanges(\'annule\', 1);"'), '>', $nbrAnnule, ' demande', ($nbrAnnule > 1 ? 's': ''), ' d\'annulation</button>';

    if (!isEdtEtuVoid($_SESSION['login'], 0))
      echo '<div style="border: 1px SOLID #000000;"></div>
      <button class="', (isset($_GET['original']) && $_GET['original'] == '1' ? 'active"' : 'notActive" onClick="seeOriginal();"'), '>L\'original</button>
      <div style="border: 1px SOLID #000000;"></div>
      <button class="', (isset($_GET['changement']) && $_GET['changement'] == '1' ? 'active"' : 'notActive" onClick="seeChangement();"'), '>Les changements</button>';
  }

  foreach ($_SESSION['tab']['etu'] as $login) {
    $info = getEtu($login);

    if ($info['mail'] == NULL)
      $name = $login;
    else
      $name = $info['nom'].' '.$info['prenom'];

    if ($mode == 'modifier' || $mode == 'planifier')
      break;
    elseif ($mode == 'afficher')
      echo '<div><button class="', (isset($_GET['login']) && is_string($_GET['login']) && ($_GET['login'] == $login) ? 'active">'.$name : 'notActive" onClick="edtEtu(\''.$login.'\')">'.$name), '</button>';
    elseif ($mode == 'comparer')
      echo '<div><button class="', (isset($_GET['login']) && is_string($_GET['login']) && ($_GET['login'] == $login) ? 'active">'.$name : 'notActive" onClick="compareEtu(\''.$login.'\')">'.$name), '</button>';
    elseif ($mode == 'organiser')
      echo '<div><button class="', (in_array($login, $_SESSION['etuActive']) ? 'etu active" style="background-color:'.$GLOBALS['colors'][array_search($login, $_SESSION['etuActive']) % count($GLOBALS['colors'])].'" onClick="delEtuActive(\''.$login.'\')">'.$name : 'etu notActive" onClick="addEtuActive(\''.$login.'\')">'.$name), '</button>';
    else
      echo '<div><button class="', (isset($_GET['login']) && is_string($_GET['login']) && ($_GET['login'] == $login) ? 'active">'.$name : 'notActive" onClick="edtEtu(\''.$login.'\')">'.$name), '</button>';

    echo '<i onClick="delTab(\''.$login.'\')" class="tab-close fa fa-times" aria-hidden="true"></i></div>';
  }

  foreach ($_SESSION['tab']['uv'] as $uv) {
    if ($mode == 'modifier' || $mode == 'planifier' || $mode == 'organiser')
      break;
    elseif ($mode == 'afficher')
      echo '<div><button class="', (isset($_GET['uv']) && is_string($_GET['uv']) && ($_GET['uv'] == $uv) ? 'active">'.$uv : 'notActive" onClick="edtUV(\''.$uv.'\')">'.$uv), '</button>';
    elseif ($mode == 'comparer')
      echo '<div><button class="', (isset($_GET['uv']) && is_string($_GET['uv']) && ($_GET['uv'] == $uv) ? 'active">'.$uv : 'notActive" onClick="compareUV(\''.$uv.'\')">'.$uv), '</button>';
    else
      echo '<div><button class="', (isset($_GET['uv']) && is_string($_GET['uv']) && ($_GET['uv'] == $uv) ? 'active">'.$uv.'</button>' : 'notActive" onClick="edtUV(\''.$uv.'\')">'.$uv.'</button>');

      echo '<i onClick="delTab(\''.$uv.'\')" class="tab-close fa fa-times" aria-hidden="true"></i></div>';
  }
?>
</div>
<div id='option'>
  <button id='addTab' onClick='searchTab();'<?php
    if ($mode == 'modifier' || $mode == 'planifier')
      echo 'class="blocked"';

    echo '><i class="fa fa-search" aria-hidden="true"></i></button>
    <select id="mode" onChange="changeMode(this.options[this.selectedIndex].value, \'', (strtotime($_SESSION['week']) < time() - 604800 ? date('Y-m-d', strtotime('monday this week')) : $_SESSION['week']), '\');">';
    $query = $GLOBALS['bdd']->prepare('SELECT login FROM etudiants WHERE login = ?');
    $GLOBALS['bdd']->execute($query, array($_SESSION['login']));

    if ($query->rowCount() == 1)
      echo '<option value=\'afficher\'', ($mode == 'afficher' ? ' selected' : ''), '>Afficher</option>
      <option value=\'comparer\'', ($mode == 'comparer' ? ' selected' : ''), '>Comparer</option>
      <option value=\'modifier\'', ($mode == 'modifier' ? ' selected' : ''), '>Modifier</option>
      <option value=\'planifier\'', ($mode == 'planifier' ? 'selected' : ''), '>Planifier</option>';
   ?><option value='organiser'<?php echo ($mode == 'organiser' ? 'selected' : ''); ?>>Organiser</option>
  </select>
</div>
