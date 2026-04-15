<?php

require_once __DIR__ . "/mysql.php";

try
{

  $pdo = new PDO(
    "mysql:host=" . MYSQL_HOST . ";dbname=" . MYSQL_NAME . ";charset=utf8mb4",
    MYSQL_USER,
    MYSQL_PASSWORD
  );

  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

}
catch (Exception $e)
{

  die("Erreur : " . $e->getMessage());

}

?>