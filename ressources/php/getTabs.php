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

?><div id='menu'><button id='sessionLogin' class=<?php
$nbrRecu = count(getRecuesList($_SESSION['login']));
$nbrEnvoi = count(getEnvoiesList($_SESSION['login']));

echo (($mode != 'organiser' && (($mode != 'modifier' && (!isset($_GET['uv']) || $_GET['uv'] == '')) || ($mode == 'modifier' && (!isset($_GET['envoi']) || $nbrEnvoi == 0) && (!isset($_GET['recu']) || $nbrRecu == 0) && !isset($_GET['original']) && !isset($_GET['changement'])) || $_GET == array('mode' => 'modifier', 'login' => '', 'addTab' => '')) && (!isset($_GET['login']) || isset($_GET['login']) && is_string($_GET['login']) && (($_GET['login'] == $_SESSION['login']) || ($_GET['login'] == '')) || empty($_GET) || (isset($_GET['tab']) && is_string($_GET['tab']) && $_GET['tab'] == 1))) ? '\'active\'>' : '\'notActive\' onClick="edtEtu(\'\');">');
echo $_SESSION['nom'], ' ', $_SESSION['prenom'], '</button>';

if ($mode == 'modifier') {
  if ($nbrRecu != 0)
    echo '<button class="', (isset($_GET['recu']) && $_GET['recu'] == '1' ? 'active' : 'notActive'), '" onClick="seeRecues();">', $nbrRecu, ' proposition', ($nbrRecu > 1 ? 's': ''), ' reçue', ($nbrRecu > 1 ? 's': ''), '</button>';
  if ($nbrEnvoi != 0)
    echo '<button class="', (isset($_GET['envoi']) && $_GET['envoi'] == '1' ? 'active' : 'notActive'), '" onClick="seeEnvoies();">', $nbrEnvoi, ' proposition', ($nbrEnvoi > 1 ? 's': ''), ' envoyée', ($nbrEnvoi > 1 ? 's': ''), '</button>';

  if (!isEdtEtuVoid($_SESSION['login'], 0)) {
    echo '<button class="', (isset($_GET['original']) && $_GET['original'] == '1' ? 'active' : 'notActive'), '" onClick="seeOriginal();">Voir l\'original</button>';
    echo '<button class="', (isset($_GET['changement']) && $_GET['changement'] == '1' ? 'active' : 'notActive'), '" onClick="seeChangement();">Voir les changements</button>';
  }
}

foreach ($_SESSION['tab']['etu'] as $login) {
  $info = getEtu($login);

  if ($info['mail'] == NULL)
    $name = $login;
  else
    $name = $info['nom'].' '.$info['prenom'];

  if ($mode == 'modifier')
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
  if ($mode == 'modifier')
    break;
  elseif ($mode == 'afficher')
    echo '<div><button class="', (isset($_GET['uv']) && is_string($_GET['uv']) && ($_GET['uv'] == $uv) ? 'active">'.$uv : 'notActive" onClick="edtUV(\''.$uv.'\')">'.$uv), '</button>';
  elseif ($mode == 'comparer')
    echo '<div><button class="', (isset($_GET['uv']) && is_string($_GET['uv']) && ($_GET['uv'] == $uv) ? 'active">'.$uv : 'notActive" onClick="compareUV(\''.$uv.'\')">'.$uv), '</button>';
  elseif ($mode == 'organiser')
    echo '<div><button class="blocked">', $uv, '</button>';
  else
    echo '<div><button class="', (isset($_GET['uv']) && is_string($_GET['uv']) && ($_GET['uv'] == $uv) ? 'active">'.$uv.'</button>' : 'notActive" onClick="edtUV(\''.$uv.'\')">'.$uv.'</button>');

    echo '<i onClick="delTab(\''.$uv.'\')" class="tab-close fa fa-times" aria-hidden="true"></i></div>';
}
?>
</div>
<div id='option'>
  <button id='addTab' onClick='searchTab();'<?php
    if ($mode == 'modifier')
    echo 'class="blocked"'; ?>><i class="fa fa-search" aria-hidden="true"></i></button>

  <select id='mode' onChange="selectMode('', this.options[this.selectedIndex].value);">
  <?php
    $query = $GLOBALS['bdd']->prepare('SELECT login FROM etudiants WHERE login = ?');
    $GLOBALS['bdd']->execute($query, array($_SESSION['login']));

    if ($query->rowCount() == 1)
      echo '<option value=\'afficher\'', ($mode == 'afficher' ? ' selected' : ''), '>Afficher</option>
      <option value=\'comparer\'', ($mode == 'comparer' ? ' selected' : ''), '>Comparer</option>
      <option value=\'modifier\'', ($mode == 'modifier' ? ' selected' : ''), '>Modifier</option>
      ';
   ?><option value='organiser'<?php echo ($mode == 'organiser' ? 'selected' : ''); ?>>Organiser</option>
  <!--  <option value='planifier'<?php echo ($mode == 'planifier' ? 'selected' : ''); ?>>Planifier</option> -->
  </select>
</div>
