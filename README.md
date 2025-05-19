# 🚀 Lancer le projet Symfony avec Docker

## 🐳 Prérequis

- Docker
- Docker Compose
- (Facultatif) Symfony CLI

---

## ⚙️ Commandes de démarrage

### 1. 🔧 Construire les conteneurs et lancer les services

```bash
docker-compose up -d --build
```

### 3. 🎼 Installer les dépendances PHP avec Composer

```bash
docker-compose exec php composer install
```

## Accès à l'application :
📍 http://localhost/login

## Accès à la doc :
📍 http://localhost/api/doc


## Mettre à jour la BDD avec les migrations :

### 1. Génère les fichiers SQL nécessaires :

```bash
docker-compose exec php php bin/console make:migration
```

### 2. Appliquer les changements à la base :

```bash
docker-compose exec php php bin/console doctrine:migrations:migrate
```

# 👋🏻 Tester l'appliccation

Pour vous connecter : mettre un utilisateur parmis user1, user2, user3, user4, user5
Ajouter le mot de passe correspondant password + i ( par exemple user1 => password1 )

Vous pouvez tester les tweets, retweet, modifications, création et détails des tweets en cliquant sur le tweet. 
Vous pouvez accéder à la liste des abonnés et abonnements en cliquant dessus. 

Vous pouvez accéder à vos notifications dans la cloche.

N'hésitez pas à tester la recherche, les likes.. Et voir comment évolue l'app en fonction de vos intéractions.
