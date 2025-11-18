FROM node:18-alpine

WORKDIR /app

# Копирование package файлов
COPY package*.json ./

# Установка зависимостей
RUN npm ci --only=production=false

# Копирование исходников
COPY . .

# Сборка проекта
RUN npm run build

# Результат сборки будет в public/build
# В production nginx будет отдавать эти файлы

