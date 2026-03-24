<?php

session_start();

require "config/database.php";

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "responsable")
{
  header("Location: connexion.php");
  exit();
}

if (isset($_POST["ajouter_club"]))
{
  $nom_club       = trim($_POST["nom_club"]);
  $description    = trim($_POST["description"]);
  $statut         = "attente";
  $date_creation  = date("Y-m-d");
  $responsable_id = $_SESSION["id"];

  if (empty($nom_club) || empty($description))
  {
    $_SESSION["club_error"] = "Veuillez remplir tous les champs.";
    header("Location: gestion_club.php");
    exit();
  }

  $sqlQuery = "
    INSERT INTO clubs
    (nom_club, description, responsable_id, statut, date_creation)
    VALUES
    (:nom_club, :description, :responsable_id, :statut, :date_creation)
  ";

  $insertClubStatement = $pdo->prepare($sqlQuery);

  $insertClubStatement->execute([
    "nom_club"       => $nom_club,
    "description"    => $description,
    "responsable_id" => $responsable_id,
    "statut"         => $statut,
    "date_creation"  => $date_creation
  ]) or die(print_r($pdo->errorInfo()));

  $_SESSION["club_success"] = "Club ajouté avec succès. Il est maintenant en attente de validation par l’administrateur.";
  header("Location: gestion_club.php");
  exit();
}

header("Location: gestion_club.php");
exit();

?>