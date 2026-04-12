# 🔐 Маршруты аутентификации

## Важно понимать!

Этот проект использует **Laravel Fortify** для аутентификации. Это значит, что маршруты `/login`, `/register`, `/logout` **НЕ видны** в файле `routes/web.php`, но они работают автоматически!

---

## Автоматические маршруты Fortify

Эти маршруты регистрируются через `FortifyServiceProvider` и используют **Inertia.js + React**:

### Вход в систему

```
GET  /login           - показать страницу входа
POST /login           - выполнить вход
```

**Что происходит:**
1. Пользователь переходит на `/login`
2. Fortify показывает React компонент `resources/js/pages/auth/login.tsx`
3. Пользователь вводит email и пароль
4. Отправляет POST форму на `/login`
5. При успехе → редирект на `/` (главная страница)

### Регистрация

```
GET  /register        - показать страницу регистрации
POST /register        - выполнить регистрацию
```

**Что происходит:**
1. Пользователь переходит на `/register`
2. Fortify показывает React компонент `resources/js/pages/auth/register.tsx`
3. Пользователь вводит имя, email, пароль
4. Отправляет POST форму на `/register`
5. Создается пользователь с ролью `user`
6. При успехе → редирект на `/` (главная страница)

### Выход из системы

```
POST /logout          - выполнить выход
```

**Что происходит:**
1. Отправляется POST форма (кнопка "Выйти")
2. Fortify завершает сессию
3. Редирект на `/login`

### Сброс пароля

```
GET  /forgot-password         - страница "Забыли пароль?"
POST /forgot-password         - отправить ссылку для сброса
GET  /reset-password/{token}  - страница сброса пароля
POST /reset-password          - выполнить сброс пароля
```

### Верификация email

```
GET  /email/verify            - страница верификации
GET  /email/verify/{id}       - подтвердить email
POST /email/verification-notification - отправить письмо снова
```

### Двухфакторная аутентификация

```
GET  /two-factor-challenge    - страница 2FA
POST /two-factor-challenge   - подтвердить 2FA
```

---

## Где находятся маршруты Fortify?

Они регистрируются автоматически пакетом Laravel Fortify. Посмотреть их можно:

```bash
php artisan route:list | grep login
php artisan route:list | grep register
php artisan route:list | grep logout
```

---

## Пользовательские маршруты (наши)

Эти маршруты видны в `routes/web.php`:

### Главная страница

```
GET  /             - редактор BibTeX (требует авторизации)
```

### Профиль пользователя

```
GET    /profile                    - показать профиль
PUT    /profile/api-key            - обновить API ключ
DELETE /profile/api-key            - удалить API ключ
PUT    /profile/name               - обновить имя
PUT    /profile/password           - обновить пароль
POST   /profile/guest/extend       - продлить гостевую сессию
POST   /profile/guest/register     - регистрация вместо гостя
```

### Гостевой вход

```
GET  /guest/login                  - страница выбора типа входа
POST /guest/login                  - войти как гость
```

### Админ-панель

```
GET  /admin                        - главная страница админки
GET  /admin/bibtex                 - управление типами
POST /admin/bibtex                 - создать тип
DELETE /admin/bibtex/{type}        - удалить тип
...и другие
```

---

## Как это работает вместе

### Сценарий: Новый пользователь

```
1. Заходит на http://localhost:8000/
2. Видит редирект на /login (потому что не авторизован)
3. Fortify показывает React страницу входа
4. Может:
   - Войти с существующим аккаунтом
   - Перейти на /register (ссылка на странице)
   - Перейти на /guest/login (страница выбора)
```

### Сценарий: Регистрация

```
1. Переходит на /register
2. Видит React форму регистрации
3. Заполняет имя, email, пароль
4. Отправляет POST /register
5. Создается User с role='user'
6. Редирект на / (главная страница редактора)
```

### Сценарий: Гостевой вход

```
1. Переходит на /guest/login
2. Видит Blade страницу выбора (guest-login.blade.php)
3. Нажимает "Войти как гость"
4. POST запрос → создается guest пользователь
5. Редирект на / (главная страница редактора)
```

---

## Почему Fortify использует React?

Этот проект настроен как **SPA (Single Page Application)** с Inertia.js:

- Fortify маршруты → React компоненты
- Наши кастомные страницы → Blade шаблоны
- Главная страница `/` → Blade (`bib.editor`)
- Профиль `/profile` → React (Inertia)

Это гибридный подход!

---

## Проверка маршрутов

Чтобы увидеть все зарегистрированные маршруты:

```bash
# Все маршруты
php artisan route:list

# Только auth маршруты
php artisan route:list --path=login
php artisan route:list --path=register
php artisan route:list --path=logout

# Только наши маршруты
php artisan route:list --path=profile
php artisan route:list --path=guest
php artisan route:list --path=admin
```

---

## Тестовые аккаунты

После запуска сидеров:

```
Email: admin@bibcheck.local
Пароль: admin123
Роль: admin

Email: user@bibcheck.local
Пароль: user123
Роль: user

Email: test@example.com
Пароль: password
Роль: user
```

---

## Настройка Fortify

Все настройки в `config/fortify.php`:

```php
'features' => [
    Features::registration(),          // Включить регистрацию
    Features::resetPasswords(),        // Включить сброс пароля
    Features::emailVerification(),     // Включить верификацию email
    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]),
],
```

---

## Создание кастомных страниц

Если хотите заменить React страницы Fortify на Blade:

1. Создайте view файл (например, `resources/views/auth/login.blade.php`)
2. Обновите `FortifyServiceProvider`:

```php
Fortify::loginView(fn (Request $request) => view('auth.login', [
    'canResetPassword' => Features::enabled(Features::resetPasswords()),
]));
```

Но сейчас используется React → это OK! ✅

---

## Краткая шпаргалка

| URL | Метод | Описание | Где определен |
|-----|-------|----------|---------------|
| `/login` | GET/POST | Вход | Fortify (автоматически) |
| `/register` | GET/POST | Регистрация | Fortify (автоматически) |
| `/logout` | POST | Выход | Fortify (автоматически) |
| `/` | GET | Главная страница | `web.php` |
| `/profile` | GET | Профиль | `web.php` |
| `/guest/login` | GET | Страница входа как гость | `web.php` |
| `/guest/login` | POST | Войти как гость | `web.php` |
| `/admin` | GET | Админ-панель | `web.php` |

---

## Важно помнить!

✅ Маршруты `/login` и `/register` **работают**, даже если их нет в `web.php`  
✅ Они используют **React компоненты**, не Blade  
✅ Fortify обрабатывает всю логику аутентификации  
✅ Наши кастомные маршруты добавлены в `web.php`  
✅ Гостевой вход - наш собственный маршрут  

---

## Если что-то не работает

```bash
# Проверить маршруты
php artisan route:list

# Очистить кеш
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:clear

# Проверить Fortify
php artisan vendor:publish --tag=fortify-config
```
