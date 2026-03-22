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
    da.*,
    c.responsable_id
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

$checkMembreQuery = "
  SELECT id
  FROM membres_club
  WHERE utilisateur_id = :utilisateur_id
    AND club_id = :club_id
";

$checkMembreStatement = $pdo->prepare($checkMembreQuery);

$checkMembreStatement->execute([
  "utilisateur_id" => $demande["utilisateur_id"],
  "club_id"        => $demande["club_id"]
]) or die(print_r($pdo->errorInfo()));

if (!$checkMembreStatement->fetch())
{
  $insertMembreQuery = "
    INSERT INTO membres_club
    (utilisateur_id, club_id, role_membre, date_entree)
    VALUES
    (:utilisateur_id, :club_id, 'membre', :date_entree)
  ";

  $insertMembreStatement = $pdo->prepare($insertMembreQuery);

  $insertMembreStatement->execute([
    "utilisateur_id" => $demande["utilisateur_id"],
    "club_id"        => $demande["club_id"],
    "date_entree"    => date("Y-m-d")
  ]) or die(print_r($pdo->errorInfo()));
}

$updateDemandeQuery = "
  UPDATE demandes_adhesion
  SET statut = 'acceptee'
  WHERE id = :demande_id
";

$updateDemandeStatement = $pdo->prepare($updateDemandeQuery);

$updateDemandeStatement->execute([
  "demande_id" => $demande_id
]) or die(print_r($pdo->errorInfo()));

$_SESSION["demande_success"] = "Demande acceptée avec succès.";
header("Location: gestion_club.php");
exit();