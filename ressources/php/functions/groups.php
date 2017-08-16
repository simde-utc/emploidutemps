<?php

  function getCustomGroups() {
    $groups = array();

    $query = $GLOBALS['db']->request(
      'SELECT * FROM students_groups WHERE login = ?',
      array($_SESSION['login'])
    );
    $data = $query->fetchAll();

    foreach ($data as $group) {
      $groups[$group['id']] = array(
        'type' => ($group['asso'] ? 'custom_asso' : 'custom'),
        'name' => $group['name'],
        'subgroups' => array()
      );

      $query = $GLOBALS['db']->request(
        'SELECT * FROM students_groups_subs WHERE idGroup = ?',
        array($group['id'])
      );
      $subs = $query->fetchAll();

      foreach ($subs as $sub) {
        $groups[$group['id']]['subgroups'][$sub['id']] = array(
          'type' => 'custom',
          'name' => $sub['name'],
          'elements' => array()
        );

        $query = $GLOBALS['db']->request(
          'SELECT * FROM students_groups_elements WHERE idSubGroup = ?',
          array($sub['id'])
        );
        $elements = $query->fetchAll();

        foreach ($elements as $element)
          $groups[$group['id']]['subgroups'][$sub['id']]['elements'][$element['element']] = $element['info'];
      }
    }

    return $groups;
  }

  function setGroups() {
    $_SESSION['active'] = array();
    $_SESSION['groups'] = array(
      'others' => array(
        'type' => 'others',
        'name' => 'Récemments consultés',
        'subgroups' => array(
          'students' => array(
            'type' => 'others',
            'name' => 'Etudiants',
            'elements' => array()
          ),
          'uvs' => array(
            'type' => 'others',
            'name' => 'UVs',
            'elements' => array()
          )
        )
      )
    );

    $roles = end(json_decode(file_get_contents('http://assos.utc.fr/profile/'.$_SESSION['login'].'/json'), TRUE)['semestres'])['roles'];
    foreach ($roles as $role) {
      $asso = $role['asso'];

      // Si on fait parti du BDE ou du SIMDE, on a le full access
      if ($asso['login'] == 'bde' || $asso['login'] == 'simde')
        $_SESSION['admin'] = TRUE;

      $_SESSION['groups'][$asso['login']] = array(
        'type' => 'asso',
        'name' => $asso['name'],
        'subgroups' => array(
          'admins' => array(
            'type' => 'asso',
            'name' => 'Bureau',
            'elements' => array()
          ),
          'resps' => array(
            'type' => 'asso',
            'name' => 'Responsables',
            'elements' => array()
          ),
          'members' => array(
            'type' => 'asso',
            'name' => 'Membres',
            'elements' => array()
          )
        )
      );

      $members = json_decode(file_get_contents('http://assos.utc.fr/asso/'.$asso['login'].'/json'), TRUE)['members'];
      foreach ($members as $member) {
          if (!$member['bureau'])
            $_SESSION['groups'][$asso['login']]['subgroups']['members']['elements'][$member['login']] = $member['role'];
          elseif (preg_match('/Resp/', $member['role']))
            $_SESSION['groups'][$asso['login']]['subgroups']['resps']['elements'][$member['login']] = $member['role'];
          else
            $_SESSION['groups'][$asso['login']]['subgroups']['admins']['elements'][$member['login']] = $member['role'];
      }
    }

    $groups = getCustomGroups();
    foreach ($groups as $id => $group) {
      if ($group['type'] != 'custom_asso')
        $_SESSION['groups'][$id] = $group;
      else {
        foreach ($group['subgroups'] as $idSub => $sub)
          $_SESSION['groups'][$group['name']]['subgroups'][$idSub] = $sub;
      }
    }
  }

  function printAddGroupTab() {
    $GLOBALS['groups']['create'] = array(
      'type' => 'new',
      'text' => 'Créer un groupe',
      'action' => 'addGroup()',
    );
  }

  function printGroupTabs() {
    foreach($_SESSION['groups'] as $name => $group) {
      $GLOBALS['groups'][$name] = array(
        'type' => $group['type'],
        'text' => $group['name'],
        'action' => 'seeGroup(\''.$name.'\')'
      );
    }

    // On déplace others à la fin (logique)
    $others = $GLOBALS['groups']['others'];
    unset($GLOBALS['groups']['others']);
    $GLOBALS['groups']['others'] = $others;

    printAddGroupTab('groups');
  }

  function printGroupTabsInfos() {
    foreach ($_SESSION['groups'] as $name => $tab) {
      printGroupTabInfos($name, $tab);
    }

    // On déplace others à la fin (logique)
    $others = $GLOBALS['groups']['others'];
    unset($GLOBALS['groups']['others']);
    $GLOBALS['groups']['others'] = $others;

    printAddGroupTab('groups');
  }

  function getGroupInfos($name, $group) {
    $infos = $group;
    $infos['active'] = FALSE;
    $infos['partialyActive'] = FALSE;
    $infos['nbr'] = 0;
    $infos['nbrExtern'] = 0;
    $infos['nbrActive'] = 0;

    if (!isset($infos['subgroups']))
      $infos['subgroups'] = array();

    foreach ($infos['subgroups'] as $sub_name => $sub_group) {
      $infos['subgroups'][$sub_name]['active'] = FALSE;
      $infos['subgroups'][$sub_name]['partialyActive'] = FALSE;
      $infos['subgroups'][$sub_name]['nbr'] = 0;
      $infos['subgroups'][$sub_name]['nbrExtern'] = 0;
      $infos['subgroups'][$sub_name]['nbrActive'] = 0;

      if ($sub_group['elements'] == array())
        continue;

      foreach ($sub_group['elements'] as $element => $info) {
        $active = array_keys($_SESSION['active'], $element) != array();
        if ($element == $_SESSION['login']) {
          $infos['subgroups'][$sub_name]['elements'][$element] = array(
            'surname' => $_SESSION['surname'],
            'firstname' => $_SESSION['firstname'],
            'email' => $_SESSION['email'],
            'extern' => FALSE,
            'active' => FALSE,
            'info' => $info
          );
          $infos['subgroups'][$sub_name]['nbr']--;
          $infos['nbr']--;
        }
        elseif (isAStudent($element)) {
          $data = getStudentInfos($element);

          $infos['subgroups'][$sub_name]['elements'][$element] = array(
            'surname' => $data['surname'],
            'firstname' => $data['firstname'],
            'email' => $data['email'],
            'extern' => FALSE,
            'active' => $active,
            'info' => $info
          );
        }
        elseif (isAnUV($element)) {
          $infos['subgroups'][$sub_name]['elements'][$element] = array(
            'uv' => $element,
            'extern' => FALSE,
            'active' => $active,
            'info' => $info
          );
        }
        else {
          $infos['subgroups'][$sub_name]['elements'][$element] = array(
            'surname' => '(en stage/extérieur)',
            'firstname' => $element,
            'email' => $element.'@etu.utc.fr',
            'active' => $active,
            'extern' => TRUE,
            'info' => $info
          );
          $infos['subgroups'][$sub_name]['nbrExtern']++;
          $infos['nbrExtern']++;
        }

        $infos['subgroups'][$sub_name]['nbr']++;
        $infos['nbr']++;

        if ($active) {
          if (!$infos['subgroups'][$sub_name]['elements'][$element]['extern']) {
            $infos['subgroups'][$sub_name]['nbrActive']++;
            $infos['nbrActive']++;
          }
        }
      }

      if ($infos['subgroups'][$sub_name]['nbrActive'] != 0)
        $infos['subgroups'][$sub_name]['partialyActive'] = TRUE;

      if ($infos['subgroups'][$sub_name]['partialyActive'] && $infos['subgroups'][$sub_name]['nbr'] == $infos['subgroups'][$sub_name]['nbrActive'] + $infos['subgroups'][$sub_name]['nbrExtern'])
        $infos['subgroups'][$sub_name]['active'] = TRUE;
    }

    if ($infos['nbrActive'] != 0) {
      $infos['partialyActive'] = TRUE;

      if ($infos['nbr'] == $infos['nbrActive'] + $infos['nbrExtern'])
        $infos['active'] = TRUE;
    }

    return $infos;
  }

  function printGroupTabInfos($name, $group) {
    $infos = getGroupInfos($name, $group);
    $all = array();

    $GLOBALS['groups'][$name] = array(
      'get' => array(
        'mode' => isset($GLOBALS['mode']) ? $GLOBALS['mode'] : 'classique',
      ),
      'options' => array(
        'all' => array(
          'text' => 'Tout le monde',
          'active' => TRUE,
          'partialyActive' => FALSE,
          'get' => array()
        )
      ),
    );

    foreach ($infos as $key => $info) {
      if (!is_array($info)) {
        $GLOBALS['groups'][$name][$key] = $info;
        continue;
      }

      foreach ($info as $sub_name => $sub_group) {
        $get = ($sub_group['active'] ? 'delActive' : 'addActive');

        if ($sub_group['nbr'] == 0)
          continue;

        $GLOBALS['groups'][$name]['options'][$sub_name] = array(
          'text' => $sub_group['name'],
          'active' => $sub_group['active'],
          'partialyActive' => $sub_group['partialyActive'],
          'get' => array(
            $get => array()
          )
        );

        foreach ($sub_group['elements'] as $element => $active) {
          if (isset($active['extern']) && $active['extern'] || $element == $_SESSION['login'])
            continue;

          array_push($GLOBALS['groups'][$name]['options'][$sub_name]['get'][$get], $element);
          $all[$element] = $active['active'];
          if ($active['active'])
            $GLOBALS['groups'][$name]['options']['all']['partialyActive'] = TRUE;
          else
            $GLOBALS['groups'][$name]['options']['all']['active'] = FALSE;
        }
      }
    }

    if ($all == array())
      unset($GLOBALS['groups'][$name]['options']['all']);
    else {
      if (!$GLOBALS['groups'][$name]['options']['all']['partialyActive'])
        $GLOBALS['groups'][$name]['options']['all']['active'] = FALSE;

      $get = ($GLOBALS['groups'][$name]['options']['all']['active'] ? 'delActive' : 'addActive');
      $GLOBALS['groups'][$name]['options']['all']['get'][$get] = array();
      foreach ($all as $element => $active)
        array_push($GLOBALS['groups'][$name]['options']['all']['get'][$get], $element);
    }

    $GLOBALS['groups'][$name]['options']['more'] = array(
      'text' => 'Plus..',
      'action' => 'seeGroup(\''.$name.'\')'
    );
  }
/*

  function printGroups() {
    $groups = array();
    foreach ($_SESSION['groups'] as $name => $group) {
      $groups[$name] = array();

      foreach ($group as $sub_name => $sub_group) {
        if (is_array($sub_group))
          array_push($groups[$name], $sub_name);
      }
    }

    return $groups;
  }



  function printGroupTabInfos($name, $group) {
    $groupActive = 1;
    $GLOBALS['groups'][$name] = array(
      'type' => NULL,
      'active' => FALSE,
      'partialyActive' => FALSE,
      'nbr' => 0,
      'nbrActive' => 0,
      'get' => array(
        'mode' => isset($GLOBALS['mode']) ? $GLOBALS['mode'] : 'classique',
      ),
      'options' => array(
        'all' => array(
          'text' => 'Tout le monde',
          'active' => FALSE,
          'partialyActive' => FALSE,
          'get' => array()
        )
      ),
      'infos' => array()
    );

    $all = array();
    $nbr = 0;
    foreach ($group as $sub_name => $sub_group) {
      if (is_string($sub_group)) {
        $GLOBALS['groups'][$name][$sub_name] = $sub_group;
        continue;
      }

      if ($sub_group == array())
        continue;

      $GLOBALS['groups'][$name]['options'][$sub_name] = array(
        'active' => ($sub_group == array() ? FALSE : TRUE),
        'partialyActive' => FALSE,
        'text' => $sub_name,
        'get' => array()
      );

      $groups = array();
      foreach ($sub_group as $login => $info) {
        if ($login != $_SESSION['login'])
          $GLOBALS['groups'][$name]['nbr']++;

        array_push($groups, $login);
        array_push($all, $login);

        if (isAStudent($login)) {
          $data = getStudentInfos($login);
          $extern = FALSE;
        }
        elseif (isAnUV($login)) {
          $data = array(
            'surname' => '(uv)',
            'firstname' => $login
          );
          $extern = FALSE;
        }
        else {
          $data = array(
            'surname' => '(en stage/extérieur)',
            'firstname' => $login
          );
          $extern = TRUE;
        }

        if ($login == $_SESSION['login'])
          $notActive = FALSE;
        else
          $notActive = array_keys($_SESSION['active'], $login) == array();

        $groupActive += $notActive;

        if ($notActive)
          $GLOBALS['groups'][$name]['options'][$sub_name]['active'] = FALSE;
        else if ($login != $_SESSION['login']) {
          if (!$extern)
            $GLOBALS['groups'][$name]['nbrActive']++;

          $GLOBALS['groups'][$name]['options']['all']['partialyActive'] = TRUE;
          $GLOBALS['groups'][$name]['options'][$sub_name]['partialyActive'] = TRUE;
        }

        $GLOBALS['groups'][$name]['infos'][$login] = array(
          'surname' => $data['surname'],
          'firstname' => $data['firstname'],
          'active' => !$notActive,
          'extern' => $extern,
          'info' => $info
        );
      }
      $nbr++;

      if ($GLOBALS['groups'][$name]['options'][$sub_name]['active']) {
        $GLOBALS['groups'][$name]['options'][$sub_name]['get']['delActive'] = $groups;
      }
      else
        $GLOBALS['groups'][$name]['options'][$sub_name]['get']['addActive'] = $groups;
    }

    if ($GLOBALS['groups'][$name]['nbrActive'] != 0) {
      $GLOBALS['groups'][$name]['partialyActive'] = TRUE;

      if ($GLOBALS['groups'][$name]['nbrActive'] == $GLOBALS['groups'][$name]['nbr'])
        $GLOBALS['groups'][$name]['active'] = TRUE;
    }

    if ($GLOBALS['groups'][$name]['nbr'] != 0 && $nbr > 1) {
      if ($GLOBALS['groups'][$name]['active']) {
        $GLOBALS['groups'][$name]['options']['all']['active'] = TRUE;
        $GLOBALS['groups'][$name]['options']['all']['get']['delActive'] = $all;
      }
      else
        $GLOBALS['groups'][$name]['options']['all']['get']['addActive'] = $all;
    }
    else
      unset($GLOBALS['groups'][$name]['options']['all']);

    // On déplace others à la fin (logique)
    if (isset($GLOBALS['groups']['others'])) {
      $others = $GLOBALS['groups']['others'];
      unset($GLOBALS['groups']['others']);
      $GLOBALS['groups']['others'] = $others;
    }

    $GLOBALS['groups'][$name]['options']['more'] = array(
      'text' => 'Plus..',
      'action' => 'seeGroup(\''.$name.'\')'
    );
  }



*/

  function addGroup($group) {
    if (isset($_SESSION['groups'][$group]))
      return FALSE;

    $GLOBALS['db']->request(
      'INSERT INTO students_groups(login, name) VALUES(?, ?)',
      array($_SESSION['login'], $group)
    );

    $query = $GLOBALS['db']->request(
      'SELECT id FROM students_groups WHERE login = ? AND name = ?',
      array($_SESSION['login'], $group)
    );

    $data = $query->fetch();

    $_SESSION['groups'][$data['id']] = array(
      'type' => 'custom',
      'name' => $group
    );
    return $data['id'];
  }

  function delGroup($idGroup) {
    if (!isset($_SESSION['groups'][$idGroup]) || $_SESSION['groups'][$idGroup]['type'] != 'custom')
      return FALSE;

    if ($idGroup != 'others') {
      $query = $GLOBALS['db']->request(
        'DELETE FROM students_groups WHERE id = ? AND login = ?',
        array($idGroup, $_SESSION['login'])
      );

      $query = $GLOBALS['db']->request(
        'DELETE FROM students_groups_subs WHERE idGroup = ?',
        array($idGroup)
      );
    }

    unset($_SESSION['groups'][$idGroup]);
    return TRUE;
  }

  function setGroup($idGroup, $name) {
    if (!isset($_SESSION['groups'][$idGroup]) || $_SESSION['groups'][$idGroup]['type'] != 'custom')
      return FALSE;

    if ($idGroup != 'others') {
      $GLOBALS['db']->request(
        'UPDATE students_groups SET name = ? WHERE id = ? AND login = ?',
        array($name, $idGroup, $_SESSION['login'])
      );
    }

    $_SESSION['groups'][$idGroup]['name'] = $name;
    return TRUE;
  }

  function addSubGroup($idGroup, $sub_group) {
    if (!isset($_SESSION['groups'][$idGroup]))
      return FALSE;

    if ($idGroup != 'others') {
      $query = $GLOBALS['db']->request(
        'SELECT * FROM students_groups_subs WHERE idGroup = ? AND name = ?',
        array($idGroup, $sub_group)
      );

      if ($query->rowCount() == 1)
        return FALSE;

      $query = $GLOBALS['db']->request(
        'INSERT INTO students_groups_subs(idGroup, name) VALUES(?, ?)',
        array($idGroup, $sub_group)
      );
    }
    else
      return FALSE;

    $query = $GLOBALS['db']->request(
      'SELECT id FROM students_groups_subs WHERE idGroup = ? AND name = ?',
      array($idGroup, $sub_group)
    );

    $data = $query->fetch();

    $_SESSION['groups'][$idGroup]['subgroups'][$data['id']] = array(
      'type' => 'custom',
      'name' => $sub_group,
      'elements' => array()
    );

    return $data['id'];
  }

  function delSubGroup($idGroup, $idSubGroup) {
    if (!isset($_SESSION['groups'][$idGroup]['subgroups'][$idSubGroup]) || $_SESSION['groups'][$idGroup]['subgroups'][$idSubGroup]['type'] != 'custom')
      return FALSE;

    if ($idGroup != 'others') {
      $query = $GLOBALS['db']->request(
        'DELETE FROM students_groups_subs WHERE idGroup = ? AND id = ?',
        array($idGroup, $idSubGroup)
      );

      $query = $GLOBALS['db']->request(
        'DELETE FROM students_groups_elements WHERE idSubGroup = ?',
        array($idSubGroup)
      );
    }

    unset($_SESSION['groups'][$idGroup]['subgroups'][$idSubGroup]);
    return TRUE;
  }

  function setSubGroup($idGroup, $idSubGroup, $name) {
    if (!isset($_SESSION['groups'][$idGroup]['subgroups'][$idSubGroup]) || $_SESSION['groups'][$idGroup]['subgroups'][$idSubGroup]['type'] != 'custom')
      return FALSE;

    if ($idGroup != 'others') {
      $GLOBALS['db']->request(
        'UPDATE students_groups_subs SET name = ? WHERE idGroup = ? AND id = ?',
        array($name, $idGroup, $idSubGroup)
      );
    }

    $_SESSION['groups'][$idGroup]['subgroups'][$idSubGroup]['name'] = $name;
    return TRUE;
  }

  function addToGroup($idGroup, $idSubGroup, $element, $info) {
    if ($element == $_SESSION['login'])
      return FALSE;

    if (!isset($_SESSION['groups'][$idGroup]['subgroups'][$idSubGroup]))
      return FALSE;

    if (isset($_SESSION['groups'][$idGroup]['subgroups'][$idSubGroup]['elements'][$element]))
      return FALSE;
    else
      $_SESSION['groups'][$idGroup]['subgroups'][$idSubGroup]['elements'][$element] = $info;

    if ($idGroup != 'others') {
      $query = $GLOBALS['db']->request(
        'INSERT INTO students_groups_elements(idSubGroup, element, info) VALUES(?, ?, ?)',
        array($idSubGroup, $element, $info)
      );
    }

    return TRUE;
  }

  function delFromGroup($idGroup, $idSubGroup, $element) {
    if ($element == $_SESSION['login'])
      return FALSE;

    if (!isset($_SESSION['groups'][$idGroup]['subgroups'][$idSubGroup]['elements'][$element]) || $_SESSION['groups'][$idGroup]['subgroups'][$idSubGroup]['type'] == 'asso')
      return FALSE;

    if ($idGroup != 'others') {
      $query = $GLOBALS['db']->request(
        'DELETE FROM students_groups_elements WHERE idSubGroup = ? AND element = ?',
        array($idSubGroup, $element)
      );

    }

    unset($_SESSION['groups'][$idGroup]['subgroups'][$idSubGroup]['elements'][$element]);
    return TRUE;
  }

  function setToGroup($idGroup, $idSubGroup, $element, $info) {
    if (!isset($_SESSION['groups'][$idGroup]['subgroups'][$idSubGroup]['elements'][$element]) || $_SESSION['groups'][$idGroup]['subgroups'][$idSubGroup]['type'] == 'asso')
      return FALSE;

    if ($idGroup != 'others') {
      $GLOBALS['db']->request(
        'UPDATE students_groups_elements SET info = ? WHERE idSubGroup = ? AND element = ?',
        array($info, $idSubGroup, $element)
      );
    }

    $_SESSION['groups'][$idGroup]['subgroups'][$idSubGroup]['elements'][$element] = $info;
    return TRUE;
  }

  function addToOthers($element) {
    if (isAnUV($element))
      return addToGroup('others', 'uvs', $element, (strstr($_SESSION['uvs'], $element) == FALSE ? (isset($_GET['info']) && is_string($_GET['info']) && !empty($_GET['info']) ? $_GET['info'] : 'Ajouté automatiquement') : 'UV suivie'));
    elseif (isAStudent($element) && $element != $_SESSION['login'])
      return addToGroup('others', 'students', $element, (isset($_GET['info']) && is_string($_GET['info']) && !empty($_GET['info']) ? $_GET['info'] : 'Ajouté.e depuis un groupe'));
    else
      return FALSE;
  }
