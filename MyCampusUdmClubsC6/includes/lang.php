<?php

if (session_status() === PHP_SESSION_NONE)
{
    session_start();
}

if (isset($_GET["lang"]) && in_array($_GET["lang"], ["fr", "en"], true))
{
    $_SESSION["lang"] = $_GET["lang"];
}

$lang = $_SESSION["lang"] ?? "fr";

$fichierLangue = __DIR__ . "/../lang/" . $lang . ".php";

if (!file_exists($fichierLangue))
{
    $fichierLangue = __DIR__ . "/../lang/fr.php";
}

$translations = require $fichierLangue;

function t(string $key): string
{
    global $translations;
    return $translations[$key] ?? $key;
}