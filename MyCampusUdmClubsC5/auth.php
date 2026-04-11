<?php

session_start();

require_once __DIR__ . "/config/database.php";

/* REGISTER */
if (isset($_POST["register"]))
{
  $nom        = trim($_POST["nom"] ?? "");
  $prenom     = trim($_POST["prenom"] ?? "");
  $email      = trim($_POST["email"] ?? "");
  $motBrut    = $_POST["motdepasse"] ?? "";
  $motdepasse = password_hash($motBrut, PASSWORD_DEFAULT);

  $role = "etudiant";

  if ($nom === "" || $prenom === "" || $email === "" || $motBrut === "")
  {
    $_SESSION["register_error"] = "Veuillez remplir tous les champs.";
    $_SESSION["active_form"] = "register";
    header("Location: connexion.php");
    exit();
  }

  $checkEmail = $pdo->prepare("
    SELECT id
    FROM utilisateurs
    WHERE email = :email
  ");

  $checkEmail->execute([
    "email" => $email
  ]);

  if ($checkEmail->fetch())
  {
    $_SESSION["register_error"] = "Email déjà enregistré.";
    $_SESSION["active_form"] = "register";
    header("Location: connexion.php");
    exit();
  }

  $insert = $pdo->prepare("
    INSERT INTO utilisateurs
    (nom, prenom, email, mot_de_passe_hash, role, statut)
    VALUES
    (:nom, :prenom, :email, :mot_de_passe_hash, :role, 'actif')
  ");

  $insert->execute([
    "nom"               => $nom,
    "prenom"            => $prenom,
    "email"             => $email,
    "mot_de_passe_hash" => $motdepasse,
    "role"              => $role
  ]);

  $_SESSION["success"] = "Compte créé avec succès.";
  $_SESSION["active_form"] = "login";
  header("Location: connexion.php");
  exit();
}

/* LOGIN */
if (isset($_POST["login"]))
{
  $email      = trim($_POST["email"] ?? "");
  $motdepasse = $_POST["motdepasse"] ?? "";

  $stmt = $pdo->prepare("
    SELECT *
    FROM utilisateurs
    WHERE email = :email
      AND statut = 'actif'
  ");

  $stmt->execute([
    "email" => $email
  ]);

  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($user && password_verify($motdepasse, $user["mot_de_passe_hash"]))
  {
    $_SESSION["id"]            = $user["id"];
    $_SESSION["nom"]           = $user["nom"];
    $_SESSION["prenom"]        = $user["prenom"];
    $_SESSION["email"]         = $user["email"];
    $_SESSION["role"]          = $user["role"];
    $_SESSION["pseudo"]        = $user["pseudo"] ?? "";
    $_SESSION["photo_profil"]  = $user["photo_profil"] ?? "";

    if ($user["role"] === "admin")
    {
      header("Location: admin.php");
    }
    elseif ($user["role"] === "responsable")
    {
      header("Location: gestion_club.php");
    }
    else
    {
      header("Location: dashboard.php");
    }

    exit();
  }

  $_SESSION["login_error"] = "Email ou mot de passe incorrect.";
  $_SESSION["active_form"] = "login";
  header("Location: connexion.php");
  exit();
}

header("Location: connexion.php");
exit();
