<?php

session_start();

if (!isset($_SESSION["id"]) || $_SESSION["role"] !== "admin")
{
  header("Location: connexion.php");
  exit();
}

require "config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST")
{
  header("Location: admin.php");
  exit();
}

$action = $_POST["action"] ?? "";

if ($action === "club_statut")
{
  $club_id = (int) ($_POST["club_id"] ?? 0);
  $statut = $_POST["statut"] ?? "";
  $statutsAutorises = ["actif", "inactif", "attente"];

  if ($club_id <= 0 || !in_array($statut, $statutsAutorises, true))
  {
    $_SESSION["admin_error"] = "Action club invalide.";
    header("Location: admin.php");
    exit();
  }

  $update = $pdo->prepare("UPDATE clubs SET statut = :statut WHERE id = :id");
  $update->execute([
    "statut" => $statut,
    "id" => $club_id
  ]);

  $_SESSION["admin_success"] = "Statut du club mis à jour.";
  header("Location: admin.php");
  exit();
}

if ($action === "user_update")
{
  $user_id = (int) ($_POST["user_id"] ?? 0);
  $role = $_POST["role"] ?? "";
  $statut = $_POST["statut"] ?? "";
  $rolesAutorises = ["etudiant", "responsable", "admin"];
  $statutsAutorises = ["actif", "inactif"];

  if ($user_id <= 0 || !in_array($role, $rolesAutorises, true) || !in_array($statut, $statutsAutorises, true))
  {
    $_SESSION["admin_error"] = "Action utilisateur invalide.";
    header("Location: admin.php");
    exit();
  }

  if ($user_id === (int) $_SESSION["id"] && $statut !== "actif")
  {
    $_SESSION["admin_error"] = "Vous ne pouvez pas désactiver votre propre compte.";
    header("Location: admin.php");
    exit();
  }

  $update = $pdo->prepare("UPDATE utilisateurs SET role = :role, statut = :statut WHERE id = :id");
  $update->execute([
    "role" => $role,
    "statut" => $statut,
    "id" => $user_id
  ]);

  $_SESSION["admin_success"] = "Utilisateur mis à jour.";
  header("Location: admin.php");
  exit();
}

$_SESSION["admin_error"] = "Action non reconnue.";
header("Location: admin.php");
exit();
