# Portfolio de Michael AKAKPOSSE

Site vitrine et portfolio PHP natif conçu à partir du cahier des charges et du modèle de données fournis. Il comprend la vitrine responsive, les études de cas, le formulaire de devis sécurisé, la mesure d’audience minimale, un tableau de bord administrateur et un espace partenaire.

## Installation locale avec MAMP

1. Dans MAMP, pointez le `DocumentRoot` vers ce dossier.
2. Créez votre configuration locale : `cp .env.example .env`.
3. Importez le dump MAMP prêt à l’emploi [PORTFOLIO.sql](PORTFOLIO.sql) dans phpMyAdmin. Si une ancienne base `PORTFOLIO` existe déjà, exécutez plutôt [upgrade_portfolio.sql](database/upgrade_portfolio.sql) une seule fois. Le script [site_vitrine_portfolio.sql](site_vitrine_portfolio.sql) reste disponible comme schéma Merise minimal pour une base vierge.
4. Installez les dépendances de courrier : `composer install`.
5. Créez votre premier administrateur depuis `http://localhost:8888/PROJET_PORTFOLIO/admin/setup.php`. Cette page n’est accessible que tant qu’aucun administrateur n’existe ; choisissez votre e-mail et un mot de passe d’au moins 12 caractères.

6. Dans `.env`, renseignez au minimum `APP_URL`, `APP_KEY`, `SITE_EMAIL`, `WHATSAPP_NUMBER`, la base de données et les paramètres SMTP. Pour le dump existant, définissez `DB_NAME=PORTFOLIO`.

L’administration est disponible sur `/admin/login.php`. Une fois connecté, utilisez « Boîte de réception » pour répondre aux demandes et « Mes projets » pour créer, modifier, classer ou illustrer les réalisations. Aucun identifiant par défaut n’est livré volontairement.

Après l’import, la vitrine lit la table `projet` directement : vous pouvez y modifier les titres, descriptions, technologies, liens de démonstration et ordre d’affichage via phpMyAdmin sans toucher aux pages PHP.

## Notifications de formulaire

Le formulaire persiste d’abord la demande en base, puis envoie :

- un e-mail au propriétaire ;
- un accusé de réception au prospect, via PHPMailer/SMTP ;
- une notification WhatsApp via le webhook configuré dans `WHATSAPP_WEBHOOK_URL`.

Le webhook doit être une passerelle serveur reliée à l’API WhatsApp Business (Meta, Twilio ou autre prestataire). Cette donnée reste côté serveur : ne placez jamais de jeton WhatsApp ou SMTP dans JavaScript.

## Mise en production

- Remplacez toutes les valeurs de `.env.example` dans `.env` et ne versionnez jamais ce dernier.
- Servez le site exclusivement en HTTPS ; activez un certificat TLS chez l’hébergeur.
- Remplacez `https://votre-domaine.tld` dans [sitemap.xml](sitemap.xml) par le domaine réel.
- Complétez les informations d’hébergement dans [mentions-legales.php](mentions-legales.php).
- Ajoutez les URLs GitHub et LinkedIn réelles dans [includes/site.php](includes/site.php).
- Ajoutez les captures réelles, liens démo et dépôts publics dans la table `projet` avant publication. Les visuels actuels sont des aperçus CSS de démonstration cohérents avec la maquette.
- Vérifiez l’envoi SMTP et le webhook WhatsApp en conditions réelles avant de communiquer l’URL.

## Sécurité intégrée

- requêtes préparées PDO, échappement HTML systématique, jeton CSRF et CSP avec nonce ;
- honeypot et une soumission de formulaire par IP pseudonymisée/minute ;
- hachage `password_hash`, verrouillage après cinq échecs pendant quinze minutes ;
- séparation stricte entre visiteur, partenaire et administrateur ;
- journalisation des actions sensibles et exports réservés à l’administrateur ;
- statistiques pseudonymisées, sans outil publicitaire tiers.

La rétention des statistiques et les sauvegardes MySQL doivent être configurées côté hébergeur selon votre politique de confidentialité.


## SEO et mise en production

Le projet inclut maintenant :

- URL publiques propres (`/services/`, `/projets/slug/`, etc.) avec redirection des anciennes URL PHP ;
- versions françaises et anglaises sous `/` et `/en/` ;
- `hreflang` FR/EN/x-default, canonical, Open Graph et Twitter Card ;
- JSON-LD `WebSite`, `Person`, `ProfessionalService`, `WebPage` et `BreadcrumbList` ;
- sitemap dynamique accessible à `/sitemap.xml` et robots dynamique à `/robots.txt` ;
- pages de projets traduites côté anglais ;
- exclusions d’indexation pour `/admin/`, `/api/`, `/espace-client/`, `.env`, `.git/` et `storage/`.

### Déploiement

1. Copier `.env.example` vers `.env`.
2. Renseigner `APP_URL` avec le domaine HTTPS final.
3. Renseigner `SITE_EMAIL`, `WHATSAPP_NUMBER`, les identifiants SMTP et la base MySQL.
4. Conserver `.env` hors Git et ne jamais publier les identifiants.
5. Importer la base SQL, puis vérifier les pages publiques.
6. Activer HTTPS sur le domaine.
7. Dans Google Search Console, ajouter le domaine et soumettre `https://votre-domaine.tld/sitemap.xml`.

Les URL propres nécessitent Apache avec `mod_rewrite` activé.
