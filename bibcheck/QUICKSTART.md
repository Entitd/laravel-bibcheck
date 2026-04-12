# 🚀 Быстрый старт - Система пользователей

## ✅ Что было добавлено

### 1. Миграции
- Добавлены поля в таблицу `users`:
  - `role` - роль (admin/user/guest)
  - `openalex_api_key` - API ключ OpenAlex
  - `is_guest` - флаг гостя
  - `guest_expires_at` - время истечения сессии

### 2. Функционал
- ✅ Роли пользователей (админ, пользователь, гость)
- ✅ Гостевые аккаунты с автоудалением (24 часа)
- ✅ Управление API ключом OpenAlex
- ✅ Страница профиля `/profile`
- ✅ Защита маршрутов по ролям
- ✅ Конвертация гостя в зарегистрированного пользователя

## 📦 Установка

### Шаг 1: Применить миграции
```bash
php artisan migrate
```

### Шаг 2: Создать тестовых пользователей (опционально)
```bash
php artisan db:seed --class=UserSeeder
```

### Шаг 3: Создать администратора (интерактивно)
```bash
php artisan app:create-admin
```

Или с параметрами:
```bash
php artisan app:create-admin --name=Admin --email=admin@test.com --password=admin12345
```

### Шаг 4: Собрать фронтенд
```bash
npm run build
```

Или для разработки:
```bash
npm run dev
```

### Шаг 5: Запустить приложение
```bash
composer run dev
```

## 👥 Тестовые аккаунты

После запуска сидеров:

| Email | Пароль | Роль |
|-------|--------|------|
| admin@bibcheck.local | admin123 | Администратор |
| user@bibcheck.local | user123 | Пользователь |
| test@example.com | password | Пользователь |

## 🎯 Основные URL

- `/` - главная страница редактора BibTeX (требует авторизации)
- `/profile` - страница профиля (требует авторизации)
- `/guest/login` - страница входа как гость (GET) или вход (POST)
- `/admin` - админ-панель (только для админов)
- `/login` - вход в систему
- `/register` - регистрация

## 🔧 Artisan команды

### Очистить просроченных гостей
```bash
php artisan app:cleanup-guests --force
```

### Создать администратора
```bash
php artisan app:create-admin
```

## 🛡️ Middleware для защиты маршрутов

```php
// Только для админов
Route::get('/admin-panel', function () {
    // ...
})->middleware('role:admin');

// Только для зарегистрированных пользователей
Route::get('/dashboard', function () {
    // ...
})->middleware('role:user');
```

## 📝 Примеры использования

### Проверка роли в коде
```php
$user = auth()->user();

if ($user->isAdmin()) {
    // Действия для админа
}

if ($user->isGuest()) {
    // Ограничения для гостей
}

// Получить API ключ
$apiKey = $user->openalex_api_key;
```

### Создание гостя
```php
use App\Services\GuestUserService;

$guestService = app(GuestUserService::class);
$guest = $guestService->createAndLoginGuest();
```

## ❗ Важные моменты

1. **Гостевые аккаунты** удаляются автоматически через 24 часа
2. **API ключи** хранятся в зашифрованном виде
3. **Middleware** `CleanupExpiredGuests` запускается автоматически
4. **Типы TypeScript** обновлены для поддержки новых полей

## 🐛 Troubleshooting

### Ошибка миграций
```bash
php artisan migrate:fresh --seed
```

### Фронтенд не работает
```bash
npm install
npm run build
```

### Ошибка прав доступа
```bash
chmod -R 775 storage
```

### Посмотреть все доступные команды
```bash
php artisan list | findstr "app:"
```

## 📚 Полная документация

Смотрите файл `USER_SYSTEM.md` для подробной документации.
