<?php

session_start();

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "responsable")
{
  header("Location: connexion.php");
  exit();
}

require "config/database.php";

if (
  !isset($_GET["id"]) || empty($_GET["id"]) ||
  !isset($_GET["club_id"]) || empty($_GET["club_id"])
)
{
  header("Location: gestion_club.php");
  exit();
}

$membre_id = (int) $_GET["id"];
$club_id   = (int) $_GET["club_id"];

$clubQuery = "
  SELECT *
  FROM clubs
  WHERE id = :club_id
    AND responsable_id = :responsable_id
";

$clubStatement = $pdo->prepare($clubQuery);

$clubStatement->execute([
  "club_id"        => $club_id,
  "responsable_id" => $_SESSION["id"]
]) or die(print_r($pdo->errorInfo()));

$club = $clubStatement->fetch(PDO::FETCH_ASSOC);

if (!$club)
{
  header("Location: gestion_club.php");
  exit();
}

$deleteQuery = "
  DELETE FROM membres_club
  WHERE id = :membre_id
    AND club_id = :club_id
";

$deleteStatement = $pdo->prepare($deleteQuery);

$deleteStatement->execute([
  "membre_id" => $membre_id,
  "club_id"   => $club_id
]) or die(print_r($pdo->errorInfo()));

$_SESSION["membre_success"] = "Membre retiré du club.";
header("Location: gestion_membres.php?id=" . $club_id);
exit();