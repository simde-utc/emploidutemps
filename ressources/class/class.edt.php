<?php
$id = 1;

function printTimeTaken($logins) {
  foreach ($logins as $login) {
    if (!isEtu($login))
      header('Location: /');
  }

  $loginList = join('", "', $logins);
  $query = $GLOBALS['bdd']->query('SELECT jour, debut, fin, semaine FROM uvs, cours WHERE login IN ("'.$loginList.'") AND (frequence = 1 OR frequence = 2) AND uvs.id = cours.id ORDER BY jour, debut, fin DESC');

  $result = $query->fetchAll();

  foreach ($result as $edt) {
    // Conversion de minutes en heures
    $debut = join('.', array(explode(':', $edt['debut'])[0], 50/30*explode(':', $edt['debut'])[1]));
    $fin = join('.', array(explode(':', $edt['fin'])[0], 50/30*explode(':', $edt['fin'])[1]));

    echo 'var task = {
    id: ', $GLOBALS['id'], ',
    column: ', $edt['jour'], ',
    duration: ', $fin - $debut, ',
    startTime: ', $debut - 7, ',
    semaine: "', $edt['semaine'], '",
    columnPerDay: 3
  }
  tasks.push(task);

  ';
  }
}


class ETU {
  private $login;
  private $edt;
  private $pic;
  const picDir = '/pic/';

  public function __construct ($login, $actuel = 1) {
    $this->login = $login;

    $this->edt = $this->getEdt($actuel);
    $this->pic = $this->setPic();
  }


  public function setEdt($actuel = 1, $echange = NULL) {
    $this->edt = $this->getEdt($actuel, $echange);
  }


  public function getEdt($actuel = 1, $echange = NULL) {
    $query = $GLOBALS['bdd']->prepare('SELECT uvs.id, uvs.uv, uvs.type, uvs.groupe, uvs.jour, uvs.debut, uvs.fin, uvs.salle, uvs.frequence, uvs.semaine, cours.color, couleurs.color AS colorUV FROM uvs, cours, couleurs WHERE cours.login = ? AND cours.actuel = ? AND (? IS NULL OR cours.echange = ?) AND uvs.uv = couleurs.uv AND uvs.id=cours.id ORDER BY uvs.jour, uvs.debut, semaine, groupe');
    $GLOBALS['bdd']->execute($query, array($this->login, $actuel, $echange, $echange));

    return $query->fetchAll();
  }

  public function printEdt($columnPerDay = 0, $echange = NULL) {
    $arrayEdt = array();

    $interraction = "<input type='button' class='option' style='color:{color}; background-color:{bColor};' onClick='edtUV({id}, &apos;{uv}&apos;);' value='Voir l&apos;edt de l&apos;UV' /><input type='button' class='option' style='color:{color}; background-color:{bColor}; width: 59px' onClick='uvMoodle({id}, &apos;{uv}&apos;);' value='Moodle' /><input type='button' class='option' style='color:{color}; background-color:{bColor}; width: 59px' onClick='uvWeb({id}, &apos;{uv}&apos;);' value='UVweb' /><input type='button' class='option' style='color:{color}; background-color:{bColor};' onClick='seeEtu({id}, &apos;{idUV}&apos;);' value='Voir les étudiants' />";

    if ($this->login == $_SESSION['login'] && $echange == NULL)
      $interraction .= "<input type='button' class='option' style='color:{color}; background-color:{bColor};' onClick='seeOthers(&apos;{login}&apos;, &apos;{uv}&apos;, &apos;{type}&apos;, &apos;{bColor}&apos;, {idUV}, {id});' value='Autres disponibilités' /><div class='color'><input type='button' class='colorButton' style=' background-color: #7DC779' onClick='changeColor({id}, {idUV}, &apos;7DC779&apos;)' value=' ' /><input type='button' class='colorButton' style=' background-color: #F9DE2D' onClick='changeColor({id}, {idUV}, &apos;F9DE2D&apos;)' value=' ' /><input type='button' class='colorButton' style=' background-color: #AB7AC6' onClick='changeColor({id}, {idUV}, &apos;AB7AC6&apos;)' value=' ' /><input type='button' class='colorButton' style=' background-color: #B0CEE9' onClick='changeColor({id}, {idUV}, &apos;B0CEE9&apos;)' value=' ' /><input type='button' class='colorButton' style=' background-color: #DF6F53' onClick='changeColor({id}, {idUV}, &apos;DF6F53&apos;)' value=' ' /><input type='button' class='colorButton' style=' background-color: #FFFFFF' onClick='changeColor({id}, {idUV}, &apos;FFFFFF&apos;)' value=' ' /><input type='button' class='colorButton' style=' background-color: #AAAAAA' onClick='changeColor({id}, {idUV}, &apos;AAAAAA&apos;)' value=' ' /><input type='button' class='colorButton' style=' background-color: #576D7C' onClick='changeColor({id}, {idUV}, &apos;576D7C&apos;)' value=' ' /><input type='button' class='colorButton' style=' background-color: #000000' onClick='changeColor({id}, {idUV}, &apos;000000&apos;)' value=' ' /><input type='button' class='colorButton'  onClick='changeColor({id}, {idUV}, &apos;NULL&apos;)' value='X' /></div>";
    /* <input type='button' class='option' style='color:{color}; background-color:{bColor};' onClick='color({id}, &apos;{login}&apos;, {idUV});' value='Modifier la couleur' /> */

    $passed = array();

    foreach ($this->edt as $edt)
      array_push($passed, array($edt['jour'], $edt['debut'], $edt['fin']));

    foreach ($this->edt as $edt) {
      $nbrSameTime = count(array_keys($passed, array($edt['jour'], $edt['debut'], $edt['fin'])));
      // Conversion de minutes en heures
      $debut = join('.', array(explode(':', $edt['debut'])[0], 50/30*explode(':', $edt['debut'])[1]));
      $fin = join('.', array(explode(':', $edt['fin'])[0], 50/30*explode(':', $edt['fin'])[1]));

      $bColor = ($edt['color'] == NULL ? $edt['colorUV'] : $edt['color']);

      if ($echange == 1)
        $bColor = '#FF0000';
      elseif ($echange == 2)
        $bColor = '#00FF00';


      if ((((hexdec(substr($bColor, 1 , 2)) * 299) + (hexdec(substr($bColor, 3 , 2)) * 587) + (hexdec(substr($bColor, 5 , 2)) * 114))) > 127000)
        $color = '#000000';
      else
        $color = '#FFFFFF';

      array_push($arrayEdt, array(
        'id' => $GLOBALS['id']++,
        'column' => $edt['jour'],
        'duration' => $fin - $debut,
        'startTime' => $debut - 7,
        'horaire' => $edt['debut'].'-'.$edt['fin'],
        'idUV' => $edt['id'],
        'uv' => $edt['uv'],
        'type' => ($edt['type'] == 'D' ? $edt['type'] = 'TD' : ($edt['type'] == 'C' ? $edt['type'] = 'Cours' : $edt['type'] = 'TP')),
        'groupe' => $edt['groupe'],
        'salle' => $edt['salle'],
        'frequence' => $edt['frequence'],
        'semaine' => $edt['semaine'],
        'note' => (($edt['semaine'] == '') ? '' : 'Sem. '.$edt['semaine']),
        'color' => $bColor,
        'nbrSameTime' => $nbrSameTime,
        'columnPerDay' => $columnPerDay
      ));
    }

    return $arrayEdt;
  }

  public function isEdtVoid() {
    return $this->edt == array();
  }

  private function setPic() {
    $picDir = $_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.$this::picDir.$this->login.'.jpg';

    if (file_exists($picDir))
      $this->pic = 'http://'.$_SERVER['SERVER_NAME'].$this::picDir.$this->login.'.jpg';
    else
      $this->pic = '';
  }

  public function getPic() {
    return $this->pic;
  }


  public function setInfo() {
    $query = $GLOBALS['bdd']->prepare('UPDATE etudiants SET mail = ?, prenom = ?, nom = ? WHERE login = ?');
    $GLOBALS['bdd']->execute($query, array($_SESSION['mail'], $_SESSION['prenom'], $_SESSION['nom'], $_SESSION['login']));
  }

  public function getInfo() {
    $query = $GLOBALS['bdd']->prepare('SELECT login, semestre, mail, prenom, nom FROM etudiants WHERE login = ?');
    $GLOBALS['bdd']->execute($query, array($this->login));

    return $query->fetch();
  }
}



class UV {
  private $uv;
  private $edt;

  public function __construct ($uv, $type = NULL) {
    $this->uv = $uv;

    $this->edt = $this->getEdt($type);
  }


  public function setEdt($type = NULL) {
    $this->edt = $this->getEdt($type);
  }


  public function getEdt($type = NULL) {
    $query = $GLOBALS['bdd']->prepare('SELECT id, uvs.uv, type, groupe, jour, debut, fin, salle, frequence, semaine, nbrEtu, color FROM uvs, couleurs WHERE uvs.uv = couleurs.uv AND uvs.uv = ? AND (? IS NULL OR type = ?) ORDER BY uv, jour, debut, semaine, groupe');
    $GLOBALS['bdd']->execute($query, array($this->uv, $type, $type));

    return $query->fetchAll();
  }

  public function printEdt($columnPerDay = 0, $userColor = NULL) {
    $interraction = "<input type='button' class='option' style='color:{color}; background-color:{bColor};' onClick='edtUV({id}, &apos;{uv}&apos;);' value='Voir l&apos;edt de l&apos;UV' /><input type='button' class='option' style='color:{color}; background-color:{bColor}; width: 59px' onClick='uvMoodle({id}, &apos;{uv}&apos;);' value='Moodle' /><input type='button' class='option' style='color:{color}; background-color:{bColor}; width: 59px' onClick='uvWeb({id}, &apos;{uv}&apos;);' value='UVweb' /><input type='button' class='option' style='color:{color}; background-color:{bColor};' onClick='seeEtu({id}, &apos;{idUV}&apos;);' value='Voir les étudiants' />";

    if ($columnPerDay == 2 && !isset($_GET['toCompare']))
      $interraction .= "<input type='button' class='option' style='color:{color}; background-color:{bColor};' onClick='exchange({id}, &apos;".(isset($_GET['login']) && is_string($_GET['login']) && !empty($_GET['login']) ? $_GET['login'] : 'snastuzz')."&apos;, &apos;{idUV}&apos;);' value='Changer de {type}' />";

    $passed = array();

    foreach ($this->edt as $edt)
      array_push($passed, array($edt['jour'], $edt['debut'], $edt['fin']));

    foreach ($this->edt as $edt) {
      $nbrSameTime = count(array_keys($passed, array($edt['jour'], $edt['debut'], $edt['fin'])));

      // Conversion de minutes en heures
      $debut = join('.', array(explode(':', $edt['debut'])[0], 50/30*explode(':', $edt['debut'])[1]));
      $fin = join('.', array(explode(':', $edt['fin'])[0], 50/30*explode(':', $edt['fin'])[1]));

      $query = $GLOBALS['bdd']->prepare('SELECT color FROM uvs, cours WHERE uvs.uv = ? AND cours.login = ? AND uvs.id = cours.id LIMIT 1');
      $GLOBALS['bdd']->execute($query, array($this->uv, $_SESSION['login']));

      if ($query->rowCount() == 0)
        $bColor = $edt['color'];
      else {
        $bColor = $query->fetch()['color'];

        if ($bColor == NULL)
          $bColor = $edt['color'];
      }

      if ((((hexdec(substr($bColor, 1 , 2)) * 299) + (hexdec(substr($bColor, 3 , 2)) * 587) + (hexdec(substr($bColor, 5 , 2)) * 114))) > 127000)
        $color = '#000000';
      else
        $color = '#FFFFFF';

      echo 'var task = {
        id: ', $GLOBALS['id'], ',
        column: ', $edt['jour'], ',
        duration: ', $fin - $debut, ',
        startTime: ', $debut - 7, ',
        horaire: "', $edt['debut'], '-', $edt['fin'], '",
        idUV: ', $edt['id'], ',
        uv: "', $edt['uv'], '",
        type: "', ($edt['type'] == 'D' ? $edt['type'] = 'TD' : ($edt['type'] == 'C' ? $edt['type'] = 'Cours' : $edt['type'] = 'TP')), '",
        groupe: "', $edt['groupe'], '",
        salle: "', $edt['salle'], '",
        frequence: ', $edt['frequence'], ',
        semaine: "', $edt['semaine'], '",
        note: "', (($edt['semaine'] == '') ? '' : 'Sem. '.$edt['semaine'].'<br>'), $edt['nbrEtu'], ' étudiants",
        interraction: "', preg_replace('/{type}/', ($edt['type'] == 'Cours' ? 'cours' : $edt['type']), preg_replace('/{color}/', $color, preg_replace('/{bColor}/', $bColor, preg_replace('/{idUV}/', $edt['id'], preg_replace('/{id}/', $GLOBALS['id']++, preg_replace('/{uv}/', $edt['uv'], $interraction)))))), '",
        color: "', $bColor, '",
        nbrSameTime: "', $nbrSameTime, '",
        columnPerDay: ', $columnPerDay, '
      }
      tasks.push(task);

      ';
    }
  }
}

?>
