# Installation locale MAMP

1. Copiez le dossier `PROJET_PORTFOLIO` dans `/Applications/MAMP/htdocs/`.
2. Démarrez Apache et MySQL dans MAMP.
3. Créez la base `PORTFOLIO` dans phpMyAdmin et importez le fichier SQL du projet s'il est fourni.
4. Copiez `.env.example` vers `.env`.
5. Pour MAMP macOS, utilisez par défaut :
   - DB_HOST=127.0.0.1
   - DB_PORT=8889
   - DB_USER=root
   - DB_PASSWORD=root
6. Laissez `APP_URL=` vide : le projet détecte automatiquement son dossier MAMP.
7. Ouvrez `http://localhost:8888/PROJET_PORTFOLIO/`.

Les liens locaux utilisent volontairement les fichiers PHP (`services.php`, `projets.php`, etc.) afin de fonctionner même si `mod_rewrite`/AllowOverride n'est pas activé dans MAMP.

Les URL propres pourront être réactivées au moment du déploiement sur un hébergement Apache correctement configuré.


## URL SEO propres

Le projet conserve les URLs PHP comme fallback fiable sur MAMP (`services.php`, `projets.php`, etc.). Si `mod_rewrite` est actif, les alias SEO suivants sont également disponibles : `/services/`, `/projets/`, `/contact/`, `/a-propos/` et `/projets/<slug>/`. Les anciennes URLs PHP restent valides.
