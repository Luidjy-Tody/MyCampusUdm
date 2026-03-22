<?php

session_start();

if (
  !isset($_SESSION["id"]) ||
  !isset($_SESSION["role"]) ||
  !in_array($_SESSION["role"], ["etudiant", "responsable"], true)
)
{
  header("Location: connexion.php");
  exit();
}

require "config/database.php";

if (!isset($_GET["id"]) || empty($_GET["id"]))
{
  $_SESSION["demande_error"] = "Club introuvable.";
  header("Location: index.php");
  exit();
}

$club_id = (int) $_GET["id"];
$utilisateur_id = (int) $_SESSION["id"];

$checkQuery = "
  SELECT id
  FROM demandes_adhesion
  WHERE utilisateur_id = :utilisateur_id
    AND club_id = :club_id
    AND statut = 'en_attente'
";

$checkStatement = $pdo->prepare($checkQuery);

$checkStatement->execute([
  "utilisateur_id" => $utilisateur_id,
  "club_id"        => $club_id
]) or die(print_r($pdo->errorInfo()));

$demande = $checkStatement->fetch(PDO::FETCH_ASSOC);

if (!$demande)
{
  $_SESSION["demande_error"] = "Aucune demande en attente à annuler.";
  header("Location: index.php");
  exit();
}

$deleteQuery = "
  DELETE FROM demandes_adhesion
  WHERE id = :id
";

$deleteStatement = $pdo->prepare($deleteQuery);

$deleteStatement->execute([
  "id" => $demande["id"]
]) or die(print_r($pdo->errorInfo()));

if ($deleteStatement->rowCount() > 0)
{
  $_SESSION["demande_success"] = "Votre demande a été annulée avec succès.";
}
else
{
  $_SESSION["demande_error"] = "La demande n’a pas pu être annulée.";
}

header("Location: index.php");
exit();