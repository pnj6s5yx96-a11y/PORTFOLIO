# Portfolio — configuration locale MAMP

## Adresse
Si le projet est placé dans `/Applications/MAMP/htdocs/PROJET_PORTFOLIO`, ouvrez :
`http://localhost:8888/PROJET_PORTFOLIO/`

Administration :
- `http://localhost:8888/PROJET_PORTFOLIO/admin/`
- secours : `http://localhost:8888/PROJET_PORTFOLIO/admin.php`
- première création du compte : `http://localhost:8888/PROJET_PORTFOLIO/admin/setup.php`

## MySQL MAMP
- hôte : `127.0.0.1`
- port : `8889`
- utilisateur : `root`
- mot de passe : `root`
- base : `PORTFOLIO`

Importez `PORTFOLIO.sql` dans phpMyAdmin MAMP si la base n’existe pas encore.

## Photo personnelle
Ajoutez votre vraie photo dans `assets/images/profile.jpg`. Le site l'utilisera automatiquement à la place du placeholder.

## Captures de projets
Depuis **Administration → Mes projets**, importez une capture JPG/PNG/WebP de chaque réalisation. Le site utilise alors automatiquement la vraie image.

## Ajout d'un projet — version simplifiée

Dans **Administration → Mes projets** :
1. Renseigne le nom, la description et les technologies.
2. Choisis directement la capture depuis ton ordinateur. Elle est enregistrée dans `assets/uploads/projects/` et son chemin local est enregistré en base.
3. Ouvre « Options supplémentaires » uniquement si tu veux ajouter une démo ou un dépôt GitHub.
4. Pour GitHub, tu peux saisir `github.com/compte/projet` : `https://` sera ajouté automatiquement.
5. Clique sur **Publier le projet**. Le projet est enregistré dans MySQL et apparaît immédiatement dans le portfolio public.

La validation n'exige plus `https://` pour une capture importée depuis l'ordinateur. Les URLs externes restent contrôlées pour éviter les liens invalides.
