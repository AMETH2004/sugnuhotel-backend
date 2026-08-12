FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip git curl \
    && docker-php-ext-install pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction

# Le lien de stockage public (photos de chambres) est recréé à chaque démarrage :
# sur un hébergeur avec système de fichiers éphémère, ce lien ne survit pas à un
# redéploiement, donc il faut le régénérer plutôt que de compter sur son existence.
RUN mkdir -p storage/framework/{sessions,views,cache} bootstrap/cache

EXPOSE 8080

# migrate --force : nécessaire car APP_ENV=production bloque les commandes destructrices
# sans ce flag. db:seed est sûr à rejouer à chaque démarrage car tous les seeders sont
# idempotents (firstOrCreate) : ça garantit des données de démo présentes même après un
# redéploiement, sans dupliquer quoi que ce soit si elles existent déjà.
CMD php artisan storage:link --force || true; \
    php artisan migrate --force && \
    php artisan db:seed --force || true; \
    php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
