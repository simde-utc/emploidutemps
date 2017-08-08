<?php
  function getStudentInfosListFromSearch($search, $begin = 0, $end = 50) {
    $query = $GLOBALS['db']->request(
      'SELECT login, semester, email, firstname, surname
        FROM students
        WHERE lower(login) LIKE lower(CONCAT("%", ?, "%")) OR lower(CONCAT(firstname, "_", surname, "_", firstname)) LIKE lower(CONCAT("%", ?, "%"))
        ORDER BY firstname, surname, login
        LIMIT ?, ?',
      array($search, $search, $begin, $end),
      array(3 => PDO::PARAM_INT, 4 => PDO::PARAM_INT)
    );

    return $query->fetchAll();
  }

  function getUVInfosListFromSearch($search, $begin = 0, $end = 50) { // Plus rapide pour la recherche (et puis chaque UV est unique dans couleurs)
    $query = $GLOBALS['db']->request(
      'SELECT uv
        FROM uvs_colors
        WHERE lower(uv) LIKE lower(CONCAT("%", ?, "%"))
        LIMIT ?, ?',
      array($search, $begin, $end),
      array(2 => PDO::PARAM_INT, 3 => PDO::PARAM_INT)
    );

    return $query->fetchAll();
  }

  function getStudentInfosFromIdUV($idUV, $status = NULL, $enabled = 1) {
    $query = $GLOBALS['db']->request(
      'SELECT students.login, students.semester, students.email, students.firstname, students.surname, students.status, uvs_followed.enabled, uvs_followed.exchanged
        FROM students, uvs_followed
        WHERE uvs_followed.idUV = ? AND uvs_followed.enabled = ? AND (? IS NULL OR students.status = ?) AND students.login = uvs_followed.login
        ORDER BY surname, firstname, login',
      array($idUV, $enabled, $status, $status)
    );

    return $query->fetchAll();
  }
