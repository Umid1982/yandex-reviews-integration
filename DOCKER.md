# Docker Инфраструктура

Полная Docker-инфраструктура для проекта с Laravel backend и Vue.js frontend.

## 📋 Структура

- **docker/php/Dockerfile** - PHP 8.2 FPM с необходимыми расширениями
- **docker/frontend/Dockerfile** - Сборка Vue.js приложения
- **docker/nginx/default.conf** - Nginx конфигурация для production
- **docker/nginx/dev.conf** - Nginx конфигурация для development
- **docker-compose.prod.yml** - Production окружение
- **docker-compose.dev.yml** - Development окружение с hot-reload

## 🚀 Быстрый старт

### Production окружение

1. **Сборка и запуск:**
```bash
docker-compose -f docker-compose.prod.yml build
docker-compose -f docker-compose.prod.yml up -d
```

2. **Установка зависимостей и настройка Laravel:**
```bash
# Войти в PHP контейнер
docker-compose -f docker-compose.prod.yml exec php bash

# Внутри контейнера:
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

3. **Сборка frontend:**
```bash
# Frontend соберётся автоматически при build контейнера
# Собранные файлы будут в ./public/build
# После сборки можно перезапустить nginx:
docker-compose -f docker-compose.prod.yml restart nginx
```

4. **Приложение доступно:**
- Frontend: http://localhost:8080
- API: http://localhost:8080/api/reviews
- MySQL: localhost:3306

### Development окружение

1. **Запуск:**
```bash
docker-compose -f docker-compose.dev.yml up -d
```

2. **Установка зависимостей:**
```bash
# PHP зависимости
docker-compose -f docker-compose.dev.yml exec php composer install

# Frontend зависимости (установятся автоматически при запуске контейнера)
# Или вручную:
docker-compose -f docker-compose.dev.yml exec frontend npm install
```

3. **Настройка Laravel:**
```bash
docker-compose -f docker-compose.dev.yml exec php bash
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

4. **Vite Dev Server:**
- Автоматически запускается на порту 5173
- Hot-reload включен
- Проксируется через nginx на /build

5. **Приложение доступно:**
- Frontend: http://localhost:8080 (с hot-reload)
- API: http://localhost:8080/api/reviews
- Vite Dev Server: http://localhost:5173

## 🔧 Обновление frontend build (Production)

После изменений во frontend коде:

```bash
# Пересобрать frontend контейнер (соберёт проект автоматически)
docker-compose -f docker-compose.prod.yml build frontend

# Перезапустить контейнеры
docker-compose -f docker-compose.prod.yml up -d frontend nginx
```

Или собрать локально и скопировать:
```bash
# Локально
npm run build

# Файлы уже будут в ./public/build, nginx их отдаст автоматически
```

## 📝 Переменные окружения

### .env файл должен содержать:

```env
APP_NAME="Quiz System"
APP_ENV=production
APP_KEY=base64:... # сгенерировать через php artisan key:generate
APP_DEBUG=false
APP_URL=http://localhost:8080

# База данных
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=quiz_system
DB_USERNAME=quiz_user
DB_PASSWORD=quiz_password

# Пользователь для входа
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=password
ADMIN_NAME=Admin
```

### Переменные в docker-compose

В `docker-compose.prod.yml` и `docker-compose.dev.yml` уже настроены:
- `DB_HOST=mysql`
- `DB_DATABASE=quiz_system`
- `DB_USERNAME=quiz_user`
- `DB_PASSWORD=quiz_password`

## 🛠 Полезные команды

### Просмотр логов
```bash
# Все сервисы
docker-compose -f docker-compose.prod.yml logs -f

# Конкретный сервис
docker-compose -f docker-compose.prod.yml logs -f php
docker-compose -f docker-compose.prod.yml logs -f nginx
docker-compose -f docker-compose.prod.yml logs -f frontend
```

### Остановка контейнеров
```bash
docker-compose -f docker-compose.prod.yml down
```

### Остановка с удалением volumes
```bash
docker-compose -f docker-compose.prod.yml down -v
```

### Перезапуск сервиса
```bash
docker-compose -f docker-compose.prod.yml restart php
docker-compose -f docker-compose.prod.yml restart nginx
```

### Выполнение команд в контейнере
```bash
# PHP контейнер
docker-compose -f docker-compose.prod.yml exec php php artisan migrate
docker-compose -f docker-compose.prod.yml exec php composer install

# Frontend контейнер
docker-compose -f docker-compose.prod.yml exec frontend npm run build
```

## 🔍 Проверка работы

1. **Проверить, что контейнеры запущены:**
```bash
docker-compose -f docker-compose.prod.yml ps
```

2. **Проверить API:**
```bash
curl http://localhost:8080/api/reviews
```

3. **Проверить frontend:**
- Открыть http://localhost:8080/reviews в браузере
- Должна загрузиться страница с отзывами

4. **Проверить базу данных:**
```bash
docker-compose -f docker-compose.prod.yml exec mysql mysql -uroot -proot -e "SHOW DATABASES;"
```

## 🐛 Решение проблем

### Ошибка подключения к базе данных
- Убедитесь, что MySQL контейнер запущен: `docker-compose ps`
- Проверьте переменные окружения в `.env`
- Проверьте healthcheck MySQL: `docker-compose logs mysql`

### Ошибка 502 Bad Gateway
- Проверьте, что PHP-FPM контейнер запущен
- Проверьте логи nginx: `docker-compose logs nginx`
- Проверьте логи php: `docker-compose logs php`

### Frontend не обновляется (dev)
- Убедитесь, что Vite dev server запущен: `docker-compose logs frontend`
- Проверьте, что порт 5173 не занят
- Перезапустите frontend контейнер: `docker-compose restart frontend`

### Права доступа
Если возникают проблемы с правами на файлы:
```bash
docker-compose -f docker-compose.prod.yml exec php chown -R www-data:www-data /var/www/html/storage
docker-compose -f docker-compose.prod.yml exec php chmod -R 755 /var/www/html/storage
```

## 📦 Структура контейнеров

### Production
- **nginx** (порт 8080) - веб-сервер, проксирует API на php-fpm, отдаёт статику
- **php** (порт 9000) - PHP-FPM для Laravel
- **mysql** (порт 3306) - база данных
- **frontend** - сборка Vue.js приложения

### Development
- **nginx** (порт 8080) - веб-сервер с проксированием Vite
- **php** (порт 9000) - PHP-FPM для Laravel
- **mysql** (порт 3306) - база данных
- **frontend** (порт 5173) - Vite Dev Server с hot-reload

## 🔐 Безопасность

Для production рекомендуется:
1. Изменить пароли MySQL в `docker-compose.prod.yml`
2. Установить `APP_DEBUG=false` в `.env`
3. Настроить SSL/TLS через nginx
4. Ограничить доступ к MySQL порту (убрать из ports или использовать firewall)

