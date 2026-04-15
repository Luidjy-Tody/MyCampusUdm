Refactor final effectué

Principales corrections :
- synchronisation du projet avec la nouvelle base `mycampus_udm`
- ajout/usage de la colonne `pseudo` dans `utilisateurs`
- page `profil.php` finalisée (pseudo, photo, infos personnelles)
- bouton flottant de notifications conservé dans le `main`, visible seulement si notifications non lues
- formulaire d'inscription aligné avec `auth.php` (création étudiant uniquement)
- `register.php` refactorisé pour déléguer proprement à `auth.php`
- suppression de l'ancienne photo profil lors d'un remplacement
- SQL complet à jour dans `mysqlrequette/mysql.sql`

Vérifications déjà faites :
- syntaxe PHP (`php -l`) : OK
- statuts utilisés dans le code :
  * demandes_adhesion : en_attente / acceptee / refusee / annulee
  * clubs : actif / inactif / attente / supprime
  * evenements : actif / annule / termine
