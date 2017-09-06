<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/include.php');
  include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/functions/groups.php');

  header('Content-Type: application/json');

  if (isset($_GET['mode']) && is_string($_GET['mode']))
    $mode = $_GET['mode'];
  else
    $mode = 'get';

  if (isset($_GET['group']) && is_string($_GET['group']))
    $group = $_GET['group'];
  else
    $group = FALSE;

  if ($mode == 'add' && $group) {
    if (isset($_GET['sub_group']) && is_string($_GET['sub_group'])) {
      if (empty($_GET['sub_group']))
        returnJSON(array('error' => 'Le sous-groupe n\'a pas de nom'));

      if (isset($_GET['element']) && is_string($_GET['element']) && !empty($_GET['element']) && isset($_GET['info']) && is_string($_GET['info'])) {
        if (isset($_GET['createSubGroup']) && $_GET['createSubGroup'] == '1') {
          $sub = addSubGroup($group, $_GET['sub_group']);

          if ($sub == FALSE)
            returnJSON(array('error' => 'Le sous-groupe existe déjà'));
        }
        else
          $sub = $_GET['sub_group'];

        if (addToGroup($group, $sub, $_GET['element'], $_GET['info']))
          returnJSON(array('success' => 'Ajouté au groupe avec cette info: '.$_GET['info']));
        else
          returnJSON(array('error' => 'Déjà présent.e dans le groupe'));
      }
      elseif (addSubGroup($group, $_GET['sub_group']))
        returnJSON(array('success' => 'Sous-groupe créé avec succès avec comme nom: '.$_GET['sub_group']));
      else
        returnJSON(array('error' => 'Le sous-groupe existe déjà'));
    }
    else {
      if (empty($group))
        returnJSON(array('error' => 'Le sous-groupe n\'a pas de nom'));

      if (addGroup($group))
        returnJSON(array('success' => 'Groupe créé avec succès avec comme nom: '.$group));
      else
        returnJSON(array('error' => 'Le groupe existe déjà'));
    }
  }
  elseif ($mode == 'del' && $group) {
    if (isset($_GET['sub_group']) && is_string($_GET['sub_group'])) {
      if (isset($_GET['element']) && is_string($_GET['element'])) {
        if (delFromGroup($group, $_GET['sub_group'], $_GET['element']))
          returnJSON(array('success' => 'Supprimé du groupe avec succès'));
        else
          returnJSON(array('error' => 'L\'élément n\'a pas été supprimé'));
      }
      elseif (delSubGroup($group, $_GET['sub_group']))
        returnJSON(array('success' => 'Sous-groupe supprimé avec succès'));
      else
        returnJSON(array('error' => 'Le sous-groupe n\'a pas été supprimé'));
    }
    else {
      if (delGroup($group))
        returnJSON(array('success' => 'Groupe supprimé avec succès'));
      else
        returnJSON(array('error' => 'Le groupe n\'a pas été supprimé'));
    }
  }
  elseif ($mode == 'set' && $group && isset($_GET['info']) && is_string($_GET['info'])) {
    $info = $_GET['info'];

    if (empty($info)) {
      if (isset($_GET['element']))
        $info = NULL;
      else
        returnJSON(array('error' => 'Impossible de changer sans texte'));
    }

    if (isset($_GET['sub_group']) && is_string($_GET['sub_group'])) {
      if (empty($_GET['sub_group']))
        returnJSON(array('error' => 'Le sous-groupe n\'a pas de d\'id'));

      if (isset($_GET['element']) && is_string($_GET['element'])) {
        if (setToGroup($group, $_GET['sub_group'], $_GET['element'], $info))
          returnJSON(array('success' => 'Info changé avec succès avec: '.$info));
        else
          returnJSON(array('error' => 'Non présent.e dans le groupe'));
      }
      elseif (setSubGroup($group, $_GET['sub_group'], $info))
        returnJSON(array('success' => 'Nom du sous-groupe changé avec succès avec: '.$info));
      else
        returnJSON(array('error' => 'Un sous-groupe existe déjà avec ce nom'));
    }
    else {
      if (setGroup($group, $info))
        returnJSON(array('success' => 'Nom du groupe changé avec succès avec: '.$info));
      else
        returnJSON(array('error' => 'Un groupe existe déjà avec ce nom'));
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

  returnJSON(array('error' => 'Erreur d\'informations'));
?>
