<?php

session_start();

$titrePage = "Calendrier";

include "includes/header.php";

?>

<section class="entete-page">
  <h1>Calendrier des clubs</h1>
  <p class="texte-gris">
    Vue globale des événements.
  </p>
</section>

<section class="barre-filtres">

  <select class="champ">
    <option>Tous les clubs</option>
  </select>

  <input class="champ" type="date">

  <input class="champ" type="search" placeholder="Rechercher un événement...">

</section>

<section class="carte">

  <h2>Événements</h2>

  <p class="texte-gris">
    Aucun événement disponible.
  </p>

</section>

<?php include "includes/footer.php"; ?>