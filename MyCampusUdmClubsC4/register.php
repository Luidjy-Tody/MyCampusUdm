<?php

session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST")
{
    header("Location: connexion.php?form=register");
    exit();
}

$_POST["register"] = $_POST["register"] ?? 1;
require __DIR__ . "/auth.php";
