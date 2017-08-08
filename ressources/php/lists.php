<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/include.php');
  include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/functions/lists.php');

  header('Content-Type: application/json');

  /*  TRAITEMENT  */

  if (isset($_GET['idUV']) && is_string($_GET['idUV']) && !empty($_GET['idUV'])) {
    echo json_encode(array(
      'students' => getStudentInfosFromIdUV($_GET['idUV'], NULL, 1)
    ));
  }
  elseif (isset($_GET['search']) && is_string($_GET['search']) && !empty($_GET['search'])) {
    $begin = (isset($_GET['begin']) && is_numeric($_GET['begin']) && !empty($_GET['begin']) ? intval($_GET['begin']) : 0);
    $nbr = (isset($_GET['nbr']) && is_numeric($_GET['nbr']) && !empty($_GET['nbr']) ? intval($_GET['nbr']) : 50);
    $students = getStudentInfosListFromSearch($_GET['search'], $begin, $nbr);
    $uvs = getUVInfosListFromSearch($_GET['search'], $begin, intval($nbr / 5));
    echo json_encode(array(
      'students' => $students,
      'uvs' => $uvs,
      'infos' => array(
        'begin' => $begin,
        'nbr' => $nbr,
        'more' => (count(getStudentInfosListFromSearch($_GET['search'], $begin + $nbr, 1)) + count(getUVInfosListFromSearch($_GET['search'], intval($begin + ($nbr / 5)), 1)) > 0),
        'uvs' => $_SESSION['uvs']
      )
    ));
  }
?>
