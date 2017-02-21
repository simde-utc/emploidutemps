<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/include.php');

  if (isset($_GET['search']) && is_string($_GET['search']) && !empty($_GET['search']))
    printEtuAndUVList(str_replace('\\', '', $_GET['search']), 100, isset($_GET['begin']) ? $_GET['begin'] : 0);
?>
