# Система пользователей - Документация

## Обзор изменений

Была добавлена полноценная система пользователей с поддержкой ролей, гостевых аккаунтов и управления API ключами.

### Что добавлено:

#### 1. База данных
- **Миграция**: `2026_04_11_000000_add_role_and_api_key_to_users_table.php`
  - `role` - роль пользователя (admin/user/guest)
  - `openalex_api_key` - API ключ OpenAlex
  - `is_guest` - флаг гостевого аккаунта
  - `guest_expires_at` - время истечения гостевой сессии

#### 2. Модель User (`app/Models/User.php`)
Новые методы:
- `isAdmin()` - проверка на администратора
- `isUser()` - проверка на обычного пользователя
- `isGuest()` - проверка на гостя
- `isGuestSessionExpired()` - проверка истечения сессии
- `isValidGuest()` - действителен ли гостевой аккаунт
- `createGuest()` - создание гостевого пользователя
- `cleanupExpiredGuests()` - очистка просроченных гостевых аккаунтов

#### 3. Middleware
- **CheckRole** (`app/Http/Middleware/CheckRole.php`)
  - Проверяет роль пользователя
  - Автоматически логаутит при истечении гостевой сессии
  - Использование: `->middleware('role:admin')`

- **CleanupExpiredGuests** (`app/Http/Middleware/CleanupExpiredGuests.php`)
  - Автоматически удаляет просроченные гостевые аккаунты
  - Запускается раз в час

#### 4. Сервисы
- **GuestUserService** (`app/Services/GuestUserService.php`)
  - `createAndLoginGuest()` - создать и авторизовать гостя
  - `getGuestSessionTimeRemaining()` - получить оставшееся время
  - `extendGuestSession()` - продлить сессию
  - `convertGuestToUser()` - конвертировать гостя в пользователя

#### 5. Контроллеры
- **ProfileController** (`app/Http/Controllers/ProfileController.php`)
  - `show()` - показать профиль
  - `updateApiKey()` - обновить API ключ
  - `deleteApiKey()` - удалить API ключ
  - `updateName()` - обновить имя
  - `updatePassword()` - обновить пароль
  - `loginAsGuest()` - войти как гость
  - `extendGuestSession()` - продлить гостевую сессию
  - `registerFromGuest()` - зарегистрироваться вместо гостя

#### 6. Роуты
Добавлены в `routes/web.php`:
```php
// Профиль (требует авторизации)
GET  /profile                    - показать профиль
PUT  /profile/api-key            - обновить API ключ
DELETE /profile/api-key          - удалить API ключ
PUT  /profile/name               - обновить имя
PUT  /profile/password           - обновить пароль
POST /profile/guest/extend       - продлить гостевую сессию
POST /profile/guest/register     - регистрация вместо гостя

// Гостевой вход
GET  /guest/login                - страница выбора типа входа
POST /guest/login                - войти как гость (форма)
```

#### 7. Фронтенд (React/Inertia)
- **Страница профиля** (`resources/js/pages/profile/Show.tsx`)
  - Отображение информации о профиле
  - Управление API ключом OpenAlex
  - Изменение имени и пароля
  - Управление гостевой сессией

- **Типы TypeScript** обновлены (`resources/js/types/auth.ts`)
  - Добавлены поля: role, openalex_api_key, is_guest, guest_expires_at

#### 8. Сидеры
- **UserSeeder** (`database/seeders/UserSeeder.php`)
  - Администратор: admin@bibcheck.local / admin123
  - Пользователь: user@bibcheck.local / user123
  - Гость: guest_test@guest.local
  - Просроченный гость: guest_expired@guest.local

## Установка и запуск

### 1. Применить миграции
```bash
php artisan migrate
```

### 2. Запустить сидеры (опционально, для тестирования)
```bash
php artisan db:seed --class=UserSeeder
```

Или все сидеры:
```bash
php artisan db:seed
```

### 3. Собрать фронтенд
```bash
npm run build
```

Или для разработки:
```bash
npm run dev
```

### 4. Запустить приложение
```bash
composer run dev
```

## Использование

### Роли пользователей

#### Администратор (admin)
- Полный доступ ко всем функциям
- Доступ к админ-панели `/admin`

#### Пользователь (user)
- Стандартный доступ
- Может управлять API ключом
- Может изменять профиль

#### Гость (guest)
- Временный аккаунт (24 часа)
- Может продлить сессию
- Может зарегистрироваться для сохранения данных
- Данные удаляются после истечения сессии

### Гостевой режим

#### Войти как гость
```
POST /guest/login
```

#### Продлить сессию
```
POST /profile/guest/extend
```

#### Зарегистрироваться вместо гостя
```
POST /profile/guest/register
Body: name, email, password, password_confirmation
```

### Управление API ключом OpenAlex

Пользователи могут сохранить свой API ключ OpenAlex в профиле:
- Перейти на `/profile`
- В разделе "API ключ OpenAlex" ввести ключ
- Сохранить

Этот ключ можно использовать в сервисах для запросов к OpenAlex API.

### Middleware для защиты роутов

```php
// Только для администраторов
Route::get('/admin', function () {
    // ...
})->middleware('role:admin');

// Только для обычных пользователей
Route::get('/dashboard', function () {
    // ...
})->middleware('role:user');
```

## Тестовые аккаунты

После запуска сидеров доступны:

| Email | Пароль | Роль | Описание |
|-------|--------|------|----------|
| admin@bibcheck.local | admin123 | admin | Администратор |
| user@bibcheck.local | user123 | user | Обычный пользователь с API ключом |
| ivan@example.com | password123 | user | Еще один пользователь |
| test@example.com | password | user | Базовый тестовый пользователь |
| guest_test@guest.local | (автосгенерирован) | guest | Активный гость |
| guest_expired@guest.local | (автосгенерирован) | guest | Просроченный гость (будет удален) |

## Автоматическая очистка

Просроченные гостевые аккаунты автоматически удаляются:
- При каждом запросе (раз в час максимум)
- Через middleware `CleanupExpiredGuests`

Также можно запустить вручную:
```php
User::cleanupExpiredGuests();
```

## Интеграция с существующим кодом

### Проверка роли в контроллере
```php
$user = auth()->user();

if ($user->isAdmin()) {
    // Действия для админа
}

if ($user->isGuest()) {
    // Ограничения для гостей
}
```

### Использование API ключа
```php
$user = auth()->user();
$apiKey = $user->openalex_api_key;

if ($apiKey) {
    // Использовать API ключ
}
```

## Безопасность

- Пароли хешируются стандартным способом Laravel
- Гостевые аккаунты имеют ограниченный срок жизни
- API ключи скрыты в ответах (не передаются на фронтенд полностью)
- Middleware автоматически логаутит просроченных гостей

## Дальнейшие улучшения

Возможные расширения:
1. [ ] Добавить аватары пользователей
2. [ ] Добавить историю действий пользователей
3. [ ] Добавить ограничения по ролям (permissions)
4. [ ] Добавить OAuth провайдеры (Google, GitHub и т.д.)
5. [ ] Добавить email уведомления о входе
6. [ ] Добавить двухфакторную аутентификацию (уже включена в Fortify)
7. [ ] Добавить блокировку аккаунтов после неудачных попыток входа

## Troubleshooting

### Миграции не запускаются
```bash
php artisan migrate:status
php artisan migrate:fresh --seed
```

### Фронтенд не работает
```bash
npm install
npm run build
```

### Ошибка прав доступа
Убедитесь, что папка storage имеет правильные права:
```bash
chmod -R 775 storage
```

### Сессия не работает
Проверьте `.env`:
```
SESSION_DRIVER=database
```

И запустите миграции для таблицы sessions.
