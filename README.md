# Emploi d'UTemps
Service SIMDE pour l'Université Technologique de Compiègne (UTC)

## L'Exporter
Le code exportable et utilisable de cette manière:
 - Faire un clone du git
 - Créer la base de données en se basant sur le db.sql
 - Créer un fichier qui contiendra toutes les infos d'accès ici: /ressouces/mdp.php
```
<?php if($_SERVER['SCRIPT_NAME'] == '/emploidutemps/ressources/mdp.php') { header('Location: /emploidutemps/'); exit; }
  const DB_HOST = 'localhost';
  const DB_NAME = 'emploidutemps';
  const DB_USER = 'emploidutemps';
  const DB_PASS = '';
  const GINGER_KEY = '';
```
