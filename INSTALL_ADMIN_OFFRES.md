# Annonces et tickets — mise à jour admin

Ce projet reprend l’archive `parc_des_princesv3(1).zip` fournie pour cette demande. Les modifications de code portent uniquement sur l’administration des annonces et tickets. Le dashboard, ses styles, les paramètres de connexion, l’accueil et les espaces Client/agents/responsables sont conservés.

## Installation

1. Sauvegarder votre dossier actuel et exporter la base depuis phpMyAdmin.
2. Dans phpMyAdmin, sélectionner votre base `parc_des_prince`, puis **Importer** → choisir `database/ajout_admin_offres.sql` de ce projet → Exécuter.
3. Utiliser le dossier complet livré pour remplacer votre copie de projet, après sauvegarde. Si votre configuration de connexion diffère de celle de l’archive fournie, conserver vos propres fichiers `admin/includes/db.php` et `Client/includes/db.php`.
4. Ouvrir le site avec votre adresse localhost habituelle puis recharger avec Ctrl+F5.
5. Dans l’administration : **Annonces / Offres**, puis **Voir / Tickets** sur une annonce.

## Base de données : seulement les trois tables prévues

Aucune nouvelle table n’est créée. `offre` et `offre_jeu` gardent leur structure. Seuls deux attributs sont ajoutés à `promotion_billet` :

| Attribut | Type | Rôle |
|---|---|---|
| id_offre | INT NULL, index et clé étrangère vers offre.id_offre | Annonce à laquelle le ticket appartient |
| categorie_ticket | VARCHAR(20), valeur initiale special | groups / special / journee |

Le script vérifie si les colonnes, l’index et la relation existent avant de les ajouter ; il peut être relancé. Il ne supprime aucune donnée. Pour les anciens tickets, `id_offre` reste NULL : l’administrateur doit les rattacher à une annonce via Modifier. Un ancien mode de tarif non reconnu doit également être choisi lors de la modification.

Pas d’ajout de `id_jeu` ou de `id_categorie` dans cette implémentation : conformément au dernier scénario convenu, les jeux sont ceux de `offre_jeu` et la catégorie Groupes/Spécial/Journée est stockée dans `categorie_ticket`, distincte de la catégorie des jeux.

Si vous aviez ajouté d’autres colonnes obligatoires sans valeur par défaut dans votre base après le dernier SQL transmis, elles doivent être prises en compte séparément avant d’utiliser ces formulaires.

## Scénario administratif

### 1. Créer l’annonce

**Annonces / Offres → Ajouter une annonce** : renseigner le nom, la description, l’image, les dates, l’état, le prix annoncé et sélectionner les jeux.

Le champ image accepte une URL HTTPS ou un chemin vers une image déjà placée dans le projet, par exemple `Client/img/CAT1.png`. Il ne téléverse pas de fichier. Les images sont visibles dans la liste et la page de détails. Le prix annoncé est informatif ; chaque ticket possède son propre tarif.

### 2. Ajouter ses tickets

Dans la liste des annonces, cliquer **Voir / Tickets**, puis **Ajouter un ticket**. L’annonce est présélectionnée.

Pour chaque ticket :

- Nom et catégorie : Groupes, Spécial ou Journée.
- Mode de calcul et valeur : prix fixe en DH par personne, réduction en % sur les tarifs des jeux inclus par personne, ou réduction en DH sur cette même somme par personne, sans prix négatif.
- `condition_min` = minimum de personnes ; `capacite_membres` = maximum de personnes.
- `delai_validite` = nombre de jours de validité prévu pour les futurs billets, à compter de la visite.
- Date de fin facultative, à l’intérieur de la période de l’annonce. Vide = reprendre la fin de l’annonce. La date de début vient de l’annonce.
- Description / conditions et état Actif / Inactif.

Les jeux sont affichés automatiquement à partir de l’annonce : tous ses tickets partagent les mêmes jeux. L’aperçu du tarif montre le montant par personne et pour le groupe minimum. C’est un aperçu d’administration, sans création de commande ni de billet.

Exemple : annonce « Vacances » avec Bowling et Mini Golf ; ticket Groupe à 30 DH/personne, minimum 4 et maximum 8 personnes. Aperçu pour 4 : 120 DH.

### 3. Modifier ou désactiver

La page **Tickets promotionnels** permet de rechercher, filtrer par annonce/catégorie/état et modifier un ticket. Désactiver conserve le ticket et ses liens, y compris les commandes existantes qui le référencent. Pour le réactiver, choisir Actif dans Modifier.

La suppression d’une annonce possédant des tickets est refusée : la passer en Indisponible permet de conserver l’historique et les associations. Une annonce sans ticket peut toujours être supprimée avec confirmation. Retirer tous les jeux d’une annonce qui possède des tickets est également refusé.

## Périmètre

Cette mise à jour ne développe ni les notifications publiques, ni l’achat des promotions, ni les signalements CIN : elle traite uniquement le scénario admin des offres validé dans cette demande.

L’accueil fourni contient déjà des requêtes vers `PROMOTION` et `CATEGORIE_TIKET`. Elles sont conservées telles quelles. L’administration réalisée ici utilise `promotion_billet` et `categorie_ticket`. La connexion des pages client à ce schéma constituera une étape ultérieure ; cette version ne prétend pas corriger leur écran de tickets.


## Vérifications

80 fichiers PHP vérifiés avec PHP 8.3 sans erreur de syntaxe ; JavaScript vérifié. Tests sur une base isolée MariaDB 10.11 avec le dump fourni (collation MySQL 8 adaptée uniquement pour le test) : script exécuté deux fois, nombre de tables inchangé, création et modification d’annonce/ticket, filtres, contrôles CSRF et validation, conservation des jeux en cas d’erreur, protection d’une annonce possédant des tickets, désactivation et anciens tickets sans annonce. La vérification visuelle dans votre navigateur reste à effectuer après installation.
