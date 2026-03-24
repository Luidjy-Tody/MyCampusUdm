<?php

require "config/database.php";

if (!isset($_SESSION["id"]))
{
  $clubs = [];
}
else
{
  $sqlQuery = "
    SELECT c.*, u.nom, u.prenom
    FROM clubs c
    LEFT JOIN utilisateurs u ON c.responsable_id = u.id
    WHERE c.responsable_id = :responsable_id
      AND c.statut <> 'supprime'
    ORDER BY c.id DESC
  ";

  $clubsStatement = $pdo->prepare($sqlQuery);

  $clubsStatement->execute([
    "responsable_id" => $_SESSION["id"]
  ]) or die(print_r($pdo->errorInfo()));

  $clubs = $clubsStatement->fetchAll(PDO::FETCH_ASSOC);
}

?>