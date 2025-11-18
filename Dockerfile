# ----------------------------------------------
# 1. FRONTEND BUILD (Node)
# ----------------------------------------------
FROM node:20 AS build

WORKDIR /app

# Устанавливаем зависимости
COPY package.json package-lock.json ./
RUN npm install

# Копируем нужные frontend файлы
COPY resources/ ./resources/
COPY vite.config.js .
COPY tailwind.config.js .
COPY postcss.config.js .

# Билдим
RUN npm run build


# ----------------------------------------------
# 2. BACKEND (PHP)
# ----------------------------------------------
FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpng-dev \
    && docker-php-ext-install pdo pdo_mysql zip gd

# Добавляем composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Копируем весь проект
COPY . .

# Копируем собранный frontend (ОЧЕНЬ ВАЖНО!)
COPY --from=build /app/public/build ./public/build

# Устанавливаем PHP зависимости
RUN composer install --no-dev --optimize-autoloader

# Чистим кэш
RUN php artisan config:clear \
    && php artisan route:clear \
    && php artisan view:clear

# Запускаем сервер Laravel
CMD php -S 0.0.0.0:${PORT} -t public
