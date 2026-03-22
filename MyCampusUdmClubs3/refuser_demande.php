<?php

session_start();

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "responsable")
{
  header("Location: connexion.php");
  exit();
}

require "config/database.php";

if (!isset($_GET["id"]) || empty($_GET["id"]))
{
  $_SESSION["demande_error"] = "Demande introuvable.";
  header("Location: gestion_club.php");
  exit();
}

$demande_id = (int) $_GET["id"];

$demandeQuery = "
  SELECT
    da.id
  FROM demandes_adhesion da
  INNER JOIN clubs c ON da.club_id = c.id
  WHERE da.id = :demande_id
    AND c.responsable_id = :responsable_id
    AND da.statut = 'en_attente'
";

$demandeStatement = $pdo->prepare($demandeQuery);

$demandeStatement->execute([
  "demande_id"     => $demande_id,
  "responsable_id" => $_SESSION["id"]
]) or die(print_r($pdo->errorInfo()));

$demande = $demandeStatement->fetch(PDO::FETCH_ASSOC);

if (!$demande)
{
  $_SESSION["demande_error"] = "Demande introuvable ou déjà traitée.";
  header("Location: gestion_club.php");
  exit();
}

$refuserQuery = "
  UPDATE demandes_adhesion
  SET statut = 'refusee'
  WHERE id = :demande_id
";

$refuserStatement = $pdo->prepare($refuserQuery);

$refuserStatement->execute([
  "demande_id" => $demande_id
]) or die(print_r($pdo->errorInfo()));

$_SESSION["demande_success"] = "Demande refusée.";
header("Location: gestion_club.php");
exit();