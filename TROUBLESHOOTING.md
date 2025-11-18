# 🔧 Решение проблем на хостинге

## 📋 Чеклист диагностики

### 1. Проверка контейнеров
```bash
docker-compose -f docker-compose.prod.yml ps
```
Все контейнеры должны быть в статусе `Up`.

### 2. Проверка логов
```bash
# Все логи
docker-compose -f docker-compose.prod.yml logs --tail=50

# Конкретные сервисы
docker-compose -f docker-compose.prod.yml logs nginx
docker-compose -f docker-compose.prod.yml logs php
docker-compose -f docker-compose.prod.yml logs mysql
docker-compose -f docker-compose.prod.yml logs frontend
```

### 3. Проверка .env файла
```bash
# Войти в PHP контейнер
docker-compose -f docker-compose.prod.yml exec php bash

# Проверить .env
cat .env | grep -E "APP_KEY|DB_|APP_URL"
```

**Обязательные переменные:**
- `APP_KEY` - должен быть сгенерирован
- `DB_HOST=mysql` (не localhost!)
- `DB_DATABASE=quiz_system`
- `DB_USERNAME=quiz_user`
- `DB_PASSWORD=quiz_password`
- `APP_URL` - должен соответствовать вашему домену

### 4. Проверка прав доступа
```bash
docker-compose -f docker-compose.prod.yml exec php chown -R www-data:www-data /var/www/html/storage
docker-compose -f docker-compose.prod.yml exec php chmod -R 775 /var/www/html/storage
docker-compose -f docker-compose.prod.yml exec php chmod -R 775 /var/www/html/bootstrap/cache
```

### 5. Проверка базы данных
```bash
# Проверить подключение
docker-compose -f docker-compose.prod.yml exec php php artisan migrate:status

# Или через MySQL клиент
docker-compose -f docker-compose.prod.yml exec mysql mysql -uroot -proot -e "SHOW DATABASES;"
```

### 6. Проверка frontend build
```bash
# Проверить, что build существует
docker-compose -f docker-compose.prod.yml exec nginx ls -la /var/www/html/public/build/

# Если нет - пересобрать
docker-compose -f docker-compose.prod.yml build frontend
docker-compose -f docker-compose.prod.yml up -d frontend
```

### 7. Проверка Nginx конфигурации
```bash
# Проверить синтаксис
docker-compose -f docker-compose.prod.yml exec nginx nginx -t

# Перезагрузить конфигурацию
docker-compose -f docker-compose.prod.yml exec nginx nginx -s reload
```

## 🐛 Типичные проблемы и решения

### Ошибка 502 Bad Gateway

**Причина:** PHP-FPM не запущен или не может подключиться.

**Решение:**
```bash
# Проверить статус PHP контейнера
docker-compose -f docker-compose.prod.yml ps php

# Проверить логи
docker-compose -f docker-compose.prod.yml logs php

# Перезапустить PHP
docker-compose -f docker-compose.prod.yml restart php
```

### Ошибка 500 Internal Server Error

**Причина:** Ошибка в Laravel (часто права доступа или .env).

**Решение:**
```bash
# Проверить логи Laravel
docker-compose -f docker-compose.prod.yml exec php tail -50 /var/www/html/storage/logs/laravel.log

# Исправить права
docker-compose -f docker-compose.prod.yml exec php chmod -R 775 storage bootstrap/cache

# Проверить .env
docker-compose -f docker-compose.prod.yml exec php php artisan config:clear
docker-compose -f docker-compose.prod.yml exec php php artisan cache:clear
```

### Ошибка подключения к базе данных

**Причина:** Неправильный `DB_HOST` или MySQL не запущен.

**Решение:**
```bash
# Проверить, что MySQL запущен
docker-compose -f docker-compose.prod.yml ps mysql

# Проверить .env (DB_HOST должен быть "mysql", не "localhost")
docker-compose -f docker-compose.prod.yml exec php cat .env | grep DB_

# Проверить подключение
docker-compose -f docker-compose.prod.yml exec php php artisan migrate:status
```

### Frontend не загружается (белый экран)

**Причина:** Vite assets не собраны или не найдены.

**Решение:**
```bash
# Проверить наличие build файлов
docker-compose -f docker-compose.prod.yml exec nginx ls -la /var/www/html/public/build/

# Пересобрать frontend
docker-compose -f docker-compose.prod.yml build frontend
docker-compose -f docker-compose.prod.yml up -d frontend

# Проверить manifest.json
docker-compose -f docker-compose.prod.yml exec nginx cat /var/www/html/public/build/.vite/manifest.json
```

### Ошибка "APP_KEY not set"

**Решение:**
```bash
docker-compose -f docker-compose.prod.yml exec php php artisan key:generate
```

### Ошибка "Class 'PDO' not found"

**Причина:** Не установлено расширение pdo_mysql.

**Решение:**
```bash
# Пересобрать PHP контейнер
docker-compose -f docker-compose.prod.yml build php
docker-compose -f docker-compose.prod.yml up -d php
```

### Страница не найдена (404)

**Причина:** Неправильный роутинг или отсутствие .htaccess (для Apache).

**Решение:**
```bash
# Проверить роуты
docker-compose -f docker-compose.prod.yml exec php php artisan route:list

# Проверить, что index.php существует
docker-compose -f docker-compose.prod.yml exec nginx ls -la /var/www/html/public/index.php
```

### CSS/JS файлы не загружаются

**Причина:** Неправильный путь к build файлам или они не собраны.

**Решение:**
```bash
# Проверить build
docker-compose -f docker-compose.prod.yml exec nginx ls -la /var/www/html/public/build/

# Пересобрать
docker-compose -f docker-compose.prod.yml build frontend
```

## 🔄 Полная переустановка

Если ничего не помогает:

```bash
# Остановить и удалить всё
docker-compose -f docker-compose.prod.yml down -v

# Пересобрать
docker-compose -f docker-compose.prod.yml build --no-cache

# Запустить
docker-compose -f docker-compose.prod.yml up -d

# Настроить Laravel
docker-compose -f docker-compose.prod.yml exec php composer install
docker-compose -f docker-compose.prod.yml exec php cp .env.example .env
docker-compose -f docker-compose.prod.yml exec php php artisan key:generate
docker-compose -f docker-compose.prod.yml exec php php artisan migrate --seed

# Проверить права
docker-compose -f docker-compose.prod.yml exec php chmod -R 775 storage bootstrap/cache
```

## 📞 Полезные команды для диагностики

```bash
# Проверить все контейнеры
docker ps

# Проверить использование ресурсов
docker stats

# Проверить сеть
docker network ls
docker network inspect quiz-system_quiz-network

# Проверить volumes
docker volume ls
docker volume inspect quiz-system_mysql_data

# Войти в контейнер
docker-compose -f docker-compose.prod.yml exec php bash
docker-compose -f docker-compose.prod.yml exec nginx sh
docker-compose -f docker-compose.prod.yml exec mysql bash
```

## 🌐 Проверка снаружи

```bash
# Проверить API
curl http://localhost:8080/api/reviews

# Проверить главную страницу
curl http://localhost:8080/login

# Проверить статические файлы
curl http://localhost:8080/build/assets/app.js
```

## ⚠️ Важные замечания для production

1. **Измените пароли MySQL** в `docker-compose.prod.yml`
2. **Установите `APP_DEBUG=false`** в `.env`
3. **Настройте SSL/TLS** через nginx
4. **Ограничьте доступ к MySQL порту** (уберите из `ports` или используйте firewall)
5. **Используйте сильные пароли** для администратора
6. **Настройте бэкапы базы данных**

