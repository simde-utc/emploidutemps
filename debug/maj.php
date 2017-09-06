<?php include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps'.'/ressources/php/include.php');
  include($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/ressources/class/class.maj.php');

  $dir = $_SERVER['DOCUMENT_ROOT'].'/emploidutemps'.'/ressources/edt';

  if (!$_SESSION['admin']) {
    echo 'Tu n\'as pas les droits';
    exit;
  }

  if (file_exists($dir.'/all.edt')) {
    echo 'Pas de mise à jour disponible';
    exit;
  }

  function rrmdir($dir) {
     if (is_dir($dir)) {
       $objects = scandir($dir);
       foreach ($objects as $object) {
         if ($object != "." && $object != "..") {
           if (is_dir($dir."/".$object))
             rrmdir($dir."/".$object);
           else
             unlink($dir."/".$object);
         }
       }
       rmdir($dir);
     }
   }

  function splitEdts($dir) {
   mkdir($dir, 0777, true);
   echo 'Splitage des emplois du temps par étudiant<br />';
   $edtFile = fopen($_SERVER['DOCUMENT_ROOT'].'/emploidutemps'.'/ressources/all.edt', 'r');
   if ($edtFile) {
     $toWrite = null;
     $login = null;
     while (($line = fgets($edtFile)) !== false) {
       if ($toWrite == null) {
         $toWrite = $line;
         $line = fgets($edtFile);
         $toWrite .= $line;
         //echo $line, '<br />';
         preg_match('/ [a-z0-9]* /', $line, $matches);
         $login = preg_replace('/ /', '', $matches[0]);
       }
       elseif (preg_match('/LE SERVICE/', $line)) {
         file_put_contents($dir.'/'.$login.'.edt', $toWrite.$line.fgets($edtFile));
         $toWrite = null;
       }
       else
         $toWrite .= $line;
     }
   }
   else {
     echo 'Erreur dans le fichier';
     exit;
   }
  }

  if (file_exists($dir)) {
    if (!file_exists($_SERVER['DOCUMENT_ROOT'].'/emploidutemps/'.'/logs/'.'login')) {
      rrmdir($dir);
      splitEdts($dir);
    }
  }
  else {
    splitEdts($dir);
  }

  if (!MAJ::insert())
    echo '<script type="text/javascript">function refresh() { window.location.href=window.location.href } setTimeout("refresh()", 100);</script>';
/*
  echo 'Reset de la BDD<br />';
  MAJ::resetBDD(); // Ne vide pas Jours au cas où elle serait déjà remplie

  echo 'Installation des emplois du temps...<br />';
  $edts = array_diff(scandir($dir), array('..', '.'));
  foreach ($edts as $edt) {
    MAJ::insertEdt($dir.'/'.$edt);
  }
*/

  unlink($_SERVER['DOCUMENT_ROOT'].'/emploidutemps'.'/ressources/all.edt');
  echo 'Installation finie !';

?>
