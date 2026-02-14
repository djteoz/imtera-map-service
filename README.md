# Imtera Map Service

Сервис для импорта и управления отзывами из Яндекс.Карт.

Стек: **PHP (runtime API)** + **Vue 3** + **TailwindCSS** + **Vite**.

## Что реализовано

- Авторизация: регистрация / вход / выход
- Настройки интеграции Яндекс
- Импорт отзывов по ссылке организации
- Пагинация и сортировка на сервере
- Карта Яндекс в настройках (ключ по умолчанию берётся из backend-конфига)

## Локальный запуск

Требования:

- PHP 8+
- Node.js 18+

Команды:

```bash
npm install
npm run dev
```

Для production-сборки:

```bash
npm run build
```

## Переменные окружения

Минимально важная переменная:

```env
YANDEX_MAPS_API_KEY=your_key_here
```

Если `yandex_maps_api_key` не задан в сохранённых настройках пользователя,
для карты используется `YANDEX_MAPS_API_KEY` из backend-конфига.

## Основные пути

- UI: `/demo.html`
- API settings: `/api/settings` и `/api/public/settings`
- API import: `/api/import` и `/api/public/import`
- API reviews: `/api/reviews` и `/api/public/reviews`

## Структура

- `app/`, `bootstrap/`, `config/`, `database/`, `routes/` — Laravel-структура
- `resources/js` — Vue-страницы и компоненты
- `resources/css`, `scss`, `tailwind.config.js` — стили и Tailwind
- `public/build` — production-ассеты Vite
- `server.php` — runtime-обработчик API и выдачи SPA

## Деплой

Подробные шаги и переменные: [DEPLOY.md](DEPLOY.md)
