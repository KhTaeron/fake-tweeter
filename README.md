# 🚀 Lancer le projet Symfony avec Docker

## 🐳 Prérequis

- Docker
- Docker Compose
- (Facultatif) Symfony CLI

---

## ⚙️ Commandes de démarrage

### 1. 🔧 Construire les conteneurs

```bash
docker-compose build
```

### 2. 🚀 Lancer les services

```bash
docker-compose up -d
```

### 3. 🎼 Installer les dépendances PHP avec Composer

```bash
docker-compose exec php composer install
```

## Accès à l'application :
📍 http://localhost:8000