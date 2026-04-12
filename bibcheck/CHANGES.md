# 📋 Система пользователей - Итоговый документ

## 🎯 Резюме

В проект Laravel + Inertia + React была интегрирована полноценная система пользователей с:
- Тремя ролями (администратор, пользователь, гость)
- Гостевыми аккаунтами с автоматическим удалением
- Управлением API ключом OpenAlex
- Страницей профиля пользователя
- Защитой маршрутов по ролям

---

## 📁 Созданные/Измененные файлы

### База данных
✅ `database/migrations/2026_04_11_000000_add_role_and_api_key_to_users_table.php`
✅ `database/seeders/UserSeeder.php`
✅ `database/seeders/DatabaseSeeder.php` (обновлен)

### Модель
✅ `app/Models/User.php` - Полностью переработана

### Middleware
✅ `app/Http/Middleware/CheckRole.php`
✅ `app/Http/Middleware/CleanupExpiredGuests.php`
✅ `bootstrap/app.php` (обновлен - регистрация middleware)

### Сервисы
✅ `app/Services/GuestUserService.php`

### Контроллеры
✅ `app/Http/Controllers/ProfileController.php`

### Actions
✅ `app/Actions/Fortify/CreateNewUser.php` (обновлен)

### Фронтенд
✅ `resources/js/pages/profile/Show.tsx`
✅ `resources/js/types/auth.ts` (обновлен)

### Команды Artisan
✅ `app/Console/Commands/CleanupGuestUsers.php`
✅ `app/Console/Commands/CreateAdminUser.php`

### Маршруты
✅ `routes/web.php` (обновлен)
  - GET `/guest/login` - страница входа как гость
  - POST `/guest/login` - выполнить вход как гость

### Документация
✅ `USER_SYSTEM.md` - Полная документация
✅ `QUICKSTART.md` - Быстрый старт
✅ `CHANGES.md` - Этот файл

---

## 🗃️ Структура базы данных

### Таблица `users` - новые поля

```sql
role VARCHAR(255) DEFAULT 'user'
  -- Роли: 'admin', 'user', 'guest'

openalex_api_key VARCHAR(255) NULLABLE
  -- API ключ OpenAlex пользователя

is_guest BOOLEAN DEFAULT FALSE
  -- Флаг гостевого аккаунта

guest_expires_at TIMESTAMP NULLABLE
  -- Время истечения гостевой сессии
```

---

## 🔐 Роли и права

### Admin (Администратор)
- ✅ Полный доступ ко всем функциям
- ✅ Доступ к админ-панели `/admin`
- ✅ Управление пользователями (в будущем)
- ✅ Все функции пользователя

### User (Пользователь)
- ✅ Доступ к профилю
- ✅ Управление API ключом
- ✅ Изменение имени и пароля
- ✅ Использование основных функций системы

### Guest (Гость)
- ✅ Временный доступ на 24 часа
- ✅ Может продлить сессию
- ✅ Может зарегистрироваться для сохранения данных
- ⚠️ Данные удаляются автоматически
- ⚠️ Не может изменить пароль

---

## 🛠️ API Эндпоинты

### Профиль (требует авторизации)
```
GET    /profile                     - Показать профиль
PUT    /profile/api-key             - Обновить API ключ
DELETE /profile/api-key             - Удалить API ключ
PUT    /profile/name                - Обновить имя
PUT    /profile/password            - Обновить пароль
POST   /profile/guest/extend        - Продлить гостевую сессию
POST   /profile/guest/register      - Регистрация вместо гостя
```

### Гостевой вход (публичный)
```
POST   /guest/login                 - Войти как гость
```

---

## 💻 Использование в коде

### Проверка роли
```php
$user = auth()->user();

if ($user->isAdmin()) {
    // Код для админа
}

if ($user->isUser()) {
    // Код для пользователя
}

if ($user->isGuest()) {
    // Код для гостя
}
```

### Middleware в роутах
```php
// Только для админов
Route::get('/admin/settings', function () {
    // ...
})->middleware('role:admin');

// Только для зарегистрированных
Route::get('/dashboard', function () {
    // ...
})->middleware('role:user');
```

### Создание гостя
```php
use App\Services\GuestUserService;

$guestService = app(GuestUserService::class);
$guest = $guestService->createAndLoginGuest();
```

### Работа с API ключом
```php
$user = auth()->user();

if ($user->openalex_api_key) {
    $client = new OpenAlexClient($user->openalex_api_key);
    // ...
}
```

---

## 🎨 Фронтенд компоненты

### Главная страница `/` (bib.editor)
Обновлена с навигационной панелью пользователя:

**Навигационная панель включает:**
- ✅ Аватар и имя пользователя
- ✅ Индикатор роли (Администратор/Пользователь/Гость)
- ✅ Индикатор подключения API ключа
- ✅ Для гостей: кнопки "Продлить сессию" и "Зарегистрироваться"
- ✅ Кнопка "Профиль" (ссылка на `/profile`)
- ✅ Кнопка "Выйти"
- ✅ Предупреждение о скором истечении гостевой сессии (< 1 часа)

### Страница профиля `/profile`
Компонент: `resources/js/pages/profile/Show.tsx`

Включает:
- ✅ Информация о пользователе
- ✅ Управление именем
- ✅ Управление API ключом (показать/скрыть/удалить/создать)
- ✅ Изменение пароля (не для гостей)
- ✅ Управление гостевой сессией (для гостей)
- ✅ Предупреждения о скором истечении

### Типы TypeScript
```typescript
type User = {
    id: number;
    name: string;
    email: string;
    role: 'admin' | 'user' | 'guest';
    openalex_api_key?: string | null;
    is_guest: boolean;
    guest_expires_at?: string | null;
    // ... остальные поля
};
```

---

## 🤖 Artisan команды

### Очистка просроченных гостей
```bash
# С подтверждением
php artisan app:cleanup-guests

# Без подтверждения
php artisan app:cleanup-guests --force
```

### Создание администратора
```bash
# Интерактивно
php artisan app:create-admin

# С параметрами
php artisan app:create-admin \
    --name=Admin \
    --email=admin@test.com \
    --password=securepassword
```

---

## ⚙️ Автоматические процессы

### Очистка гостевых аккаунтов
- **Механизм**: Middleware `CleanupExpiredGuests`
- **Частота**: Раз в час (кешируется)
- **Что делает**: Удаляет все просроченные гостевые аккаунты

### Истечение гостевой сессии
- **Длительность**: 24 часа с момента создания
- **Продление**: Еще 24 часа через `/profile/guest/extend`
- **При истечении**: Автоматический логаут

---

## 🔒 Безопасность

### Пароли
- ✅ Хеширование через `bcrypt`
- ✅ Минимум 8 символов
- ✅ Проверка на компрометацию (в production)

### API ключи
- ✅ Скрыты в JSON ответах
- ✅ Не передаются полностью на фронтенд
- ✅ Можно удалить в любой момент

### Гостевые аккаунты
- ✅ Автоматическое удаление
- ✅ Ограниченное время жизни
- ✅ Нельзя использовать важные функции

### Middleware защиты
- ✅ Автоматический логаут при истечении сессии
- ✅ Проверка ролей на каждом запросе
- ✅ Защита админ-панели

---

## 📦 Зависимости

Все необходимые зависимости уже установлены:
- ✅ Laravel Fortify (аутентификация)
- ✅ Inertia.js (SPA)
- ✅ React (фронтенд)
- ✅ TailwindCSS (стили)

Ничего дополнительно устанавливать не нужно!

---

## 🚀 Запуск системы

```bash
# 1. Миграции
php artisan migrate

# 2. Тестовые данные (опционально)
php artisan db:seed --class=UserSeeder

# 3. Фронтенд
npm run build

# 4. Запуск
composer run dev
```

---

## 🐛 Известные проблемы и решения

### Проблема: Миграции не запускаются
**Решение**:
```bash
php artisan migrate:status
php artisan migrate:fresh --seed
```

### Проблема: Фронтенд не видит типы
**Решение**:
```bash
npm install
npm run build
```

### Проблема: Гость не может войти
**Решение**: Проверьте, что миграции применены:
```bash
php artisan migrate
```

### Проблема: Сессия не сохраняется
**Решение**: Проверьте `.env`:
```env
SESSION_DRIVER=database
```

---

## 📊 Статистика изменений

- **Создано файлов**: 12
- **Изменено файлов**: 6
- **Строк кода добавлено**: ~1500
- **Миграций**: 1
- **Моделей обновлено**: 1
- **Контроллеров создано**: 1
- **Middleware создано**: 2
- **Сервисов создано**: 1
- **Команд создано**: 2
- **React компонентов**: 1

---

## 🎓 Рекомендации по развитию

### Ближайшие улучшения:
1. Добавить аватары пользователей (storage + intervention/image)
2. Добавить логирование действий
3. Добавить систему разрешений (spatie/laravel-permission)
4. Добавить OAuth (Google, GitHub)
5. Добавить уведомления об активности

### Долгосрочные планы:
1. Многоязычность (i18n)
2. Темная/светлая тема (уже есть)
3. API для мобильных приложений
4. Двухфакторная аутентификация (уже включена)
5. Резервное копирование данных пользователей

---

## 📞 Поддержка

При проблемах:
1. Проверьте логи: `storage/logs/laravel.log`
2. Проверьте миграции: `php artisan migrate:status`
3. Проверьте роуты: `php artisan route:list`
4. Очистите кеш: `php artisan cache:clear`

---

## ✨ Готово!

Система пользователей полностью реализована и готова к использованию.

Для быстрого старта используйте файл `QUICKSTART.md`.
Для полной документации - `USER_SYSTEM.md`.

**Приятной разработки! 🎉**
