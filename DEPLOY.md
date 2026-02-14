# Imtera Map Service - Деплой на Railway

## Быстрый старт

### 1. Подготовка проекта

```bash
cd imtera-map-service
git init
git add .
git commit -m "Initial commit"
```

### 2. Создание проекта на Railway

1. Зайдите на [railway.app](https://railway.app)
2. Войдите через GitHub
3. Нажмите **New Project** → **Deploy from GitHub repo**
4. Выберите ваш репозиторий (или создайте новый и запушьте код)

### 3. Настройка переменных окружения

В панели Railway добавьте переменные:

```env
APP_NAME=Imtera
APP_ENV=production
APP_KEY=base64:ГЕНЕРИРУЕТСЯ_АВТОМАТИЧЕСКИ
APP_DEBUG=false
APP_URL=${{RAILWAY_PUBLIC_DOMAIN}}

DB_CONNECTION=sqlite
DB_DATABASE=/app/database/database.sqlite

CACHE_DRIVER=file
SESSION_DRIVER=file
LOG_LEVEL=error
YANDEX_MAPS_API_KEY=REPLACE_WITH_IMTERA_YANDEX_MAPS_KEY
```

### 4. Генерация APP_KEY

После первого деплоя выполните в Railway Shell:

```bash
php artisan key:generate --show
```

Скопируйте полученный ключ и добавьте в переменную `APP_KEY`.

### 5. Создание базы данных

В Railway Shell выполните:

```bash
mkdir -p database
touch database/database.sqlite
php artisan migrate --force
```

### 6. Доступ к приложению

После успешного деплоя:

- Frontend: `https://ваш-домен.railway.app/demo.html?page=login`
- API: `https://ваш-домен.railway.app/api/reviews`

Учетные данные демо: `demo@imtera.ru` / `demo12345`

## Локальная разработка

Если нужно работать локально БЕЗ PHP:

```bash
npm run dev
# Открыть http://localhost:5173/demo.html?page=login
```

Приложение будет работать на localStorage без backend.

## Обновление проекта

```bash
git add .
git commit -m "Update"
git push
```

Railway автоматически пересоберет и задеплоит.

## Структура проекта

- `/app` - Laravel backend (API контроллеры, сервисы)
- `/resources/js` - Vue 3 фронтенд
- `/public` - Статические файлы
- `/dist` - Собранный фронтенд (создается при билде)
- `/routes/api.php` - API маршруты
- `Procfile` - Команда запуска для Railway
- `nixpacks.toml` - Конфигурация билда
