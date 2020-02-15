<?php
  function getStudentInfosListFromSearch() {

    $query = $GLOBALS['db']->request(
      'SELECT count(id) AS nbr, students.login, semester, email, firstname, surname FROM uvs_followed, students WHERE idUV IN (SELECT idUV FROM `uvs_followed` where uvs_followed.login = ?) AND uvs_followed.login != ? AND uvs_followed.login = students.login GROUP BY uvs_followed.login ORDER BY nbr desc LIMIT 10',
      array($_SESSION['login'], $_SESSION['login'])
    );

    return $query->fetchAll();
  }
