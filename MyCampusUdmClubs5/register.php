<?php

session_start();

require_once "config/database.php";


if(isset($_POST["register"]))
{

    $nom        = $_POST["nom"];
    $prenom     = $_POST["prenom"];
    $email      = $_POST["email"];
    $motdepasse = password_hash($_POST["motdepasse"], PASSWORD_DEFAULT);
    $role       = $_POST["role"];


    /* vérifier si email existe */

    $checkEmail = $pdo->prepare("
        SELECT id_utilisateur
        FROM utilisateurs
        WHERE email = :email
    ");

    $checkEmail->execute([
        "email" => $email
    ]);


    if($checkEmail->fetch())
    {

        $_SESSION["register_error"] = "Email déjà utilisé";

        header("Location: connexion.php");
        exit();

    }



    /* insertion utilisateur */

    $insert = $pdo->prepare("
        INSERT INTO utilisateurs
        (nom, prenom, email, motdepasse, role)
        VALUES
        (:nom, :prenom, :email, :motdepasse, :role)
    ");


    $insert->execute([

        "nom"        => $nom,
        "prenom"     => $prenom,
        "email"      => $email,
        "motdepasse" => $motdepasse,
        "role"       => $role

    ]);


    $_SESSION["success"] = "Compte créé avec succès";


    header("Location: connexion.php");
    exit();

}

?>