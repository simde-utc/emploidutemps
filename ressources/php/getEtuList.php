<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/php/include.php');

  if (isset($_GET['idUV']) && is_string($_GET['idUV']) && !empty($_GET['idUV']))
    printEtuList($_GET['idUV']);
?>
