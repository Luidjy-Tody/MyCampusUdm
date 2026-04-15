Migration appliquée vers la nouvelle base de données.

Nouvelles colonnes dans utilisateurs :
- pseudo
- photo_profil
- telephone
- filiere
- bio

Nouvelles colonnes dans clubs :
- categorie
- image_club
- statut avec valeur supprime
- date_creation en TIMESTAMP

Nouvelles colonnes dans evenements :
- image_event
- capacite_max
- statut avec valeur termine
- date_creation

Nouvelles tables ajoutées :
- inscriptions_evenement
- notifications
- newsletter_abonnes
- newsletter_envois
- galerie_club
- avis_evenement

Fichiers mis à jour :
- auth.php
- includes/header.php
- dashboard.php
- demande_adhesion.php
- accepter_demande.php
- refuser_demande.php
- annuler_demande.php
- gestion_evenements.php
- newsletter_subscribe.php
- newsletter_admin.php
- css/style.css
- mysqlrequette/mysql.sql

Nouveaux fichiers créés :
- profil.php
- notifications.php
- desabonnement.php
- includes/app_helpers.php
