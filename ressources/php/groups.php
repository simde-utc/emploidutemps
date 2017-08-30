<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/include.php');
  include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/functions/groups.php');

  header('Content-Type: application/json');

  function returnJSON($array) {
    echo json_encode($array);
    exit;
  }

  if (isset($_GET['mode']) && is_string($_GET['mode']))
    $mode = $_GET['mode'];
  else
    $mode = 'get';

  if (isset($_GET['group']) && is_string($_GET['group']))
    $group = $_GET['group'];
  else
    $group = FALSE;

  if ($group == '')
    $group = FALSE;

  if ($mode == 'add' && $group) {
    if (isset($_GET['sub_group']) && is_string($_GET['sub_group'])) {
      if (empty($_GET['sub_group']))
        returnJSON(array('error' => 'Le sous-groupe n\'a pas de nom'));

      if (isset($_GET['element']) && is_string($_GET['element']) && isset($_GET['info']) && is_string($_GET['info'])) {
        if (isset($_GET['createSubGroup']) && $_GET['createSubGroup'] == '1') {
          $sub = addSubGroup($group, $_GET['sub_group']);

          if ($sub == FALSE)
            returnJSON(array('error' => 'Le sous-groupe existe déjà'));
        }
        else
          $sub = $_GET['sub_group'];

        if (addToGroup($group, $sub, $_GET['element'], $_GET['info']))
          returnJSON(array('info' => 'Ajouté au groupe avec cette info: '.$_GET['info']));
        else
          returnJSON(array('error' => 'Déjà présent.e dans le groupe'));
      }
      elseif (addSubGroup($group, $_GET['sub_group']))
        returnJSON(array('info' => 'Sous-groupe créé avec succès avec comme nom: '.$_GET['sub_group']));
      else
        returnJSON(array('error' => 'Le sous-groupe existe déjà'));
    }
    else {
      if (addGroup($group))
        returnJSON(array('info' => 'Groupe créé avec succès avec comme nom: '.$group));
      else
        returnJSON(array('error' => 'Le groupe existe déjà'));
    }
  }
  elseif ($mode == 'del' && $group) {
    if (isset($_GET['sub_group']) && is_string($_GET['sub_group'])) {
      if (isset($_GET['element']) && is_string($_GET['element'])) {
        if (delFromGroup($group, $_GET['sub_group'], $_GET['element']))
          returnJSON(array('info' => 'Supprimé du groupe avec succès'));
        else
          returnJSON(array('error' => 'L\'élément n\'a pas été supprimé'));
      }
      elseif (delSubGroup($group, $_GET['sub_group']))
        returnJSON(array('info' => 'Sous-groupe supprimé avec succès'));
      else
        returnJSON(array('error' => 'Le sous-groupe n\'a pas été supprimé'));
    }
    else {
      if (delGroup($group))
        returnJSON(array('info' => 'Groupe supprimé avec succès'));
      else
        returnJSON(array('error' => 'Le groupe n\'a pas été supprimé'));
    }
  }
  elseif ($mode == 'set' && $group && isset($_GET['info']) && is_string($_GET['info'])) {
    $info = $_GET['info'];

    if (isset($_GET['sub_group']) && is_string($_GET['sub_group'])) {
      if (empty($_GET['sub_group']))
        returnJSON(array('error' => 'Le sous-groupe n\'a pas de nom'));

      if (isset($_GET['element']) && is_string($_GET['element'])) {
        if (setToGroup($group, $_GET['sub_group'], $_GET['element'], $info))
          returnJSON(array('info' => 'Info changé avec succès avec: '.$info));
        else
          returnJSON(array('error' => 'Non présent.e dans le groupe'));
      }
      elseif (setSubGroup($group, $_GET['sub_group'], $info))
        returnJSON(array('info' => 'Nom du sous-groupe changé avec succès avec: '.$info));
      else
        returnJSON(array('error' => 'Le sous-groupe n\'existe pas'));
    }
    else {
      if (setGroup($group, $info))
        returnJSON(array('info' => 'Nom du groupe changé avec succès avec: '.$info));
      else
        returnJSON(array('error' => 'Le groupe n\'existe pas'));
    }
  }
  elseif ($mode == 'get' && !$group) {
    $groups = array();
    foreach ($_SESSION['groups'] as $name => $group) {
      $groups[$name] = getGroupInfos($name, $group);
    }
    returnJSON(array('groups' => $groups));
  }
  elseif (isset($_SESSION['groups'][$group]))
    returnJSON(array($group => getGroupInfos($group, $_SESSION['groups'][$group])));
  else
    returnJSON(array('error' => 'Aucun groupe donné ou trouvé'));
?>
