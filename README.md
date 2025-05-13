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
📍 http://localhost:8000


## Mettre à jour la BDD avec les migrations :

### 1. Génère les fichiers SQL nécessaires :

```bash
docker-compose exec php php bin/console make:migration
```

### 2. Appliquer les changements à la base :

```bash
docker-compose exec php php bin/console doctrine:migrations:migrate
```
