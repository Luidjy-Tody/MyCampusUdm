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

$clubQuery = "
  SELECT responsable_id
  FROM clubs
  WHERE id = :club_id
";

$clubStatement = $pdo->prepare($clubQuery);

$clubStatement->execute([
  "club_id" => $club_id
]) or die(print_r($pdo->errorInfo()));

$club = $clubStatement->fetch(PDO::FETCH_ASSOC);

if (!$club)
{
  $_SESSION["demande_error"] = "Club introuvable.";
  header("Location: index.php");
  exit();
}

if (
  $_SESSION["role"] === "responsable" &&
  (int) $club["responsable_id"] === $utilisateur_id
)
{
  $_SESSION["demande_error"] = "Vous êtes déjà responsable de ce club.";
  header("Location: index.php");
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
  "utilisateur_id" => $utilisateur_id,
  "club_id"        => $club_id
]) or die(print_r($pdo->errorInfo()));

if ($checkMembreStatement->fetch())
{
  $_SESSION["demande_error"] = "Vous êtes déjà membre de ce club.";
  header("Location: index.php");
  exit();
}

$checkDemandeQuery = "
  SELECT id
  FROM demandes_adhesion
  WHERE utilisateur_id = :utilisateur_id
    AND club_id = :club_id
    AND statut = 'en_attente'
";

$checkDemandeStatement = $pdo->prepare($checkDemandeQuery);

$checkDemandeStatement->execute([
  "utilisateur_id" => $utilisateur_id,
  "club_id"        => $club_id
]) or die(print_r($pdo->errorInfo()));

if ($checkDemandeStatement->fetch())
{
  $_SESSION["demande_error"] = "Une demande est déjà en attente pour ce club.";
  header("Location: index.php");
  exit();
}

$insertQuery = "
  INSERT INTO demandes_adhesion
  (utilisateur_id, club_id, statut, date_demande)
  VALUES
  (:utilisateur_id, :club_id, 'en_attente', :date_demande)
";

$insertStatement = $pdo->prepare($insertQuery);

$insertStatement->execute([
  "utilisateur_id" => $utilisateur_id,
  "club_id"        => $club_id,
  "date_demande"   => date("Y-m-d")
]) or die(print_r($pdo->errorInfo()));

$_SESSION["demande_success"] = "Votre demande d’adhésion a été envoyée.";

header("Location: index.php");
exit();