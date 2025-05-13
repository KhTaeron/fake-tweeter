# Utilise l'image PHP officielle avec le CLI
FROM php:8.4-fpm

# Installation des dépendances pour PostgreSQL et d'autres outils nécessaires
RUN apt-get update && apt-get install -y libpq-dev git unzip && docker-php-ext-install pdo pdo_pgsql

# Installation de Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers du projet Symfony dans le conteneur
COPY . .

# Installer les dépendances de Symfony via Composer
RUN composer install

# Exposer le port par défaut de PHP-FPM
EXPOSE 9000

CMD ["php-fpm"]
