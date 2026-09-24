# Déploiement du portfolio — version SEO

## 1. Avant l'envoi sur le serveur

1. Copiez `.env.example` en `.env` sur le serveur.
2. Renseignez `APP_URL` avec l'URL HTTPS réelle du site, par exemple `https://www.exemple.com`.
3. Renseignez la base MySQL et les paramètres SMTP.
4. Renseignez `SITE_EMAIL`, `WHATSAPP_NUMBER`, `GITHUB_URL` et `LINKEDIN_URL` si nécessaire.
5. Ne publiez jamais `.env` dans Git ou dans un dossier accessible publiquement.

## 2. Pré-requis serveur

- PHP 8.1 ou plus récent
- MySQL/MariaDB
- Apache avec `mod_rewrite` activé
- HTTPS actif
- Extensions PHP utilisées par le projet, notamment PDO/MySQL et mbstring en production

## 3. Base de données

Importez `PORTFOLIO.sql` ou `site_vitrine_portfolio.sql` selon la base utilisée par votre installation. Vérifiez les paramètres dans `.env`.

## 4. URLs publiques SEO

Les anciennes URLs PHP sont redirigées vers les URLs propres :

- `/a-propos/`
- `/projets/`
- `/projets/pressing-manager/`
- `/services/`
- `/contact/`
- `/mentions-legales/`
- `/en/`
- `/en/about/`
- `/en/projects/`
- `/en/projects/pressing-manager/`
- `/en/services/`
- `/en/contact/`
- `/en/legal/`

Le fichier `.htaccess` effectue les redirections et réécrit les URLs propres vers PHP.

## 5. Sitemap et robots

Après déploiement, vérifier :

- `https://VOTRE-DOMAINE.com/sitemap.xml`
- `https://VOTRE-DOMAINE.com/robots.txt`

Le sitemap est généré dynamiquement et inclut les pages françaises, anglaises et les projets publiés.

## 6. Google Search Console

1. Ajoutez le domaine dans Google Search Console.
2. Vérifiez la propriété.
3. Soumettez `https://VOTRE-DOMAINE.com/sitemap.xml`.
4. Inspectez l'accueil, les services, les projets et quelques études de cas.
5. Vérifiez que le canonical choisi correspond aux URLs propres.
6. Vérifiez les variantes linguistiques FR/EN et les rapports de pages indexées/non indexées.

## 7. Vérifications après mise en ligne

- Le site répond en HTTPS.
- Les anciennes URLs PHP renvoient une redirection 301 vers les URLs propres.
- `/admin/`, `/api/`, `/espace-client/`, `.env`, `.git/` et `storage/` ne sont pas indexables/accessibles publiquement.
- Les images principales ont un texte alternatif pertinent.
- Les pages FR et EN contiennent un contenu réellement traduit.
- Les données structurées sont valides et correspondent au contenu visible.
