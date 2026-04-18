<x-layouts.app>
    @section('breadcrumb', 'Профиль')

    @php
        $roleLabels = [
            'admin' => 'Администратор',
            'user' => 'Пользователь',
            'guest' => 'Гость',
        ];

        $roleLabel = $roleLabels[$user['role']] ?? 'Пользователь';
        $hasApiKey = filled($user['openalex_api_key'] ?? null);
        $guestSession = $user['guest_session'] ?? null;
    @endphp

    <div class="mx-auto max-w-5xl">
        @if (session('success'))
            <div class="profile-alert profile-alert-success mb-5 rounded-2xl px-4 py-3 text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="profile-alert profile-alert-error mb-5 rounded-2xl px-4 py-3 text-sm">
                <div class="font-semibold text-slate-900">Не удалось сохранить изменения.</div>
                <ul class="mt-2 space-y-1 text-slate-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="profile-hero rounded-[2rem] px-6 py-6 sm:px-8">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <div class="profile-kicker">Личный кабинет</div>
                    <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl">
                        Профиль пользователя
                    </h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600 sm:text-base">
                        Здесь можно сохранить персональный API key для OpenAlex. После этого проверки будут
                        выполняться с использованием вашего ключа вместо общего ключа приложения.
                    </p>
                </div>

                <div class="profile-badge-grid">
                    <div class="profile-chip">
                        <span class="profile-chip-label">Роль</span>
                        <span class="profile-chip-value">{{ $roleLabel }}</span>
                    </div>
                    <div class="profile-chip">
                        <span class="profile-chip-label">OpenAlex</span>
                        <span class="profile-chip-value">{{ $hasApiKey ? 'Ключ подключён' : 'Ключ не задан' }}</span>
                    </div>
                </div>
            </div>
        </section>

        @if ($user['is_guest'] && $guestSession)
            <section class="warning-note mt-5 rounded-2xl px-5 py-4">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div class="text-sm font-bold text-amber-950">Гостевая сессия активна</div>
                        <div class="mt-1 text-sm text-amber-900">
                            До окончания сессии осталось: {{ $guestSession['remaining_formatted'] }}.
                            @if ($guestSession['is_expiring_soon'])
                                Сессия истекает меньше чем через час.
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <form action="{{ route('profile.guest.extend') }}" method="POST">
                            @csrf
                            <button type="submit" class="action-button action-button-warm">
                                Продлить сессию
                            </button>
                        </form>

                        <a href="{{ route('profile.guest.register.form') }}" class="action-button action-button-dark">
                            Зарегистрироваться
                        </a>
                    </div>
                </div>
            </section>
        @endif

        <div class="mt-6 grid gap-6 xl:grid-cols-[1.05fr_1.45fr]">
            <section class="page-surface rounded-[1.8rem] p-6">
                <div class="section-caption">Данные аккаунта</div>
                <div class="flex items-start gap-4">
                    <div class="user-avatar flex h-16 w-16 shrink-0 items-center justify-center rounded-[1.25rem] text-lg font-bold text-white">
                        {{ mb_strtoupper(mb_substr($user['name'], 0, 2)) }}
                    </div>
                    <div class="min-w-0">
                        <div class="text-xl font-bold text-slate-950">{{ $user['name'] }}</div>
                        <div class="mt-1 break-all text-sm text-slate-500">{{ $user['email'] }}</div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="role-pill {{ $user['is_guest'] ? 'role-pill-guest' : ($user['role'] === 'admin' ? 'role-pill-admin' : 'role-pill-user') }}">
                                {{ $roleLabel }}
                            </span>
                            @if ($hasApiKey)
                                <span class="status-chip text-sm font-semibold text-teal-800">
                                    <span class="h-2.5 w-2.5 rounded-full bg-teal-500"></span>
                                    Ключ сохранён
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <dl class="mt-6 space-y-4">
                    <div class="profile-meta-row">
                        <dt>ID пользователя</dt>
                        <dd>{{ $user['id'] }}</dd>
                    </div>
                    <div class="profile-meta-row">
                        <dt>Email</dt>
                        <dd>{{ $user['email'] }}</dd>
                    </div>
                    <div class="profile-meta-row">
                        <dt>Роль</dt>
                        <dd>{{ $roleLabel }}</dd>
                    </div>
                    <div class="profile-meta-row">
                        <dt>Статус OpenAlex</dt>
                        <dd>{{ $hasApiKey ? 'Подключён персональный ключ' : 'Используется ключ приложения или запросы без ключа' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="page-surface rounded-[1.8rem] p-6">
                <div class="section-caption">Интеграция OpenAlex</div>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight text-slate-950">API key OpenAlex</h2>
                        <p class="mt-2 max-w-2xl text-sm leading-7 text-slate-600">
                            Вставьте ваш ключ, чтобы использовать персональные лимиты и настройки OpenAlex в проверках
                            библиографии.
                        </p>
                    </div>
                    @if ($hasApiKey)
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">
                            Активно
                        </span>
                    @endif
                </div>

                <form action="{{ route('profile.apikey.update') }}" method="POST" class="mt-6 space-y-5">
                    @csrf
                    @method('PUT')

                    <div>
                        <a href="https://openalex.org/" target="_blank" rel="noopener noreferrer" for="openalex_api_key" class="form-label">OpenAlex API key</a>
                        <input
                            id="openalex_api_key"
                            name="openalex_api_key"
                            type="password"
                            value="{{ old('openalex_api_key', $user['openalex_api_key'] ?? '') }}"
                            placeholder="Введите ваш API key"
                            class="form-input @error('openalex_api_key') form-input-error @enderror"
                            autocomplete="off"
                        >
                        @error('openalex_api_key')
                            <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-2 text-sm text-slate-500">
                            Ключ хранится в вашем профиле и подставляется в запросы к OpenAlex автоматически.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-4">
                        <div class="text-sm font-semibold text-slate-900">Что изменится после сохранения</div>
                        <ul class="mt-3 space-y-2 text-sm leading-6 text-slate-600">
                            <li>Проверка источников будет отправлять ваш API key в OpenAlex.</li>
                            <li>Если поле пустое, приложение вернётся к ключу из конфигурации.</li>
                            <li>Ключ можно обновить или удалить в любой момент.</li>
                        </ul>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <button type="submit" class="action-button action-button-dark">
                            {{ $hasApiKey ? 'Обновить ключ' : 'Сохранить ключ' }}
                        </button>
                    </div>
                </form>

                @if ($hasApiKey)
                    <form action="{{ route('profile.apikey.delete') }}" method="POST" class="mt-3">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="action-button action-button-danger">
                            Удалить ключ
                        </button>
                    </form>
                @endif
            </section>
        </div>
    </div>

    <style>
        .profile-hero {
            border: 1px solid rgba(191, 219, 254, 0.5);
            background:
                radial-gradient(circle at top right, rgba(20, 184, 166, 0.14), transparent 28%),
                linear-gradient(135deg, rgba(239, 246, 255, 0.88) 0%, rgba(240, 253, 250, 0.9) 55%, rgba(255, 255, 255, 0.92) 100%);
            box-shadow: 0 28px 48px -40px rgba(14, 116, 144, 0.32);
            backdrop-filter: blur(16px);
        }

        .profile-kicker,
        .section-caption {
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #94a3b8;
        }

        .profile-badge-grid {
            display: grid;
            gap: 0.85rem;
            min-width: min(100%, 17rem);
        }

        .profile-chip {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            border: 1px solid rgba(226, 232, 240, 0.82);
            border-radius: 1.2rem;
            background: rgba(255, 255, 255, 0.78);
            padding: 0.95rem 1rem;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.72);
        }

        .profile-chip-label {
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #94a3b8;
        }

        .profile-chip-value {
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
        }

        .profile-alert {
            border: 1px solid rgba(226, 232, 240, 0.86);
            box-shadow: 0 18px 30px -32px rgba(15, 23, 42, 0.2);
        }

        .profile-alert-success {
            background: linear-gradient(135deg, rgba(236, 253, 245, 0.96) 0%, rgba(240, 253, 250, 0.98) 100%);
            border-color: rgba(110, 231, 183, 0.55);
            color: #065f46;
        }

        .profile-alert-error {
            background: linear-gradient(135deg, rgba(255, 241, 242, 0.96) 0%, rgba(255, 247, 237, 0.98) 100%);
            border-color: rgba(253, 164, 175, 0.5);
            color: #9f1239;
        }

        .page-surface {
            border: 1px solid rgba(226, 232, 240, 0.92);
            background: rgba(255, 255, 255, 0.84);
            box-shadow: 0 24px 40px -38px rgba(15, 23, 42, 0.2);
            backdrop-filter: blur(14px);
        }

        .warning-note {
            border: 1px solid rgba(253, 230, 138, 0.74);
            background: linear-gradient(135deg, rgba(255, 251, 235, 0.94) 0%, rgba(255, 247, 237, 0.98) 100%);
        }

        .user-avatar {
            background: linear-gradient(135deg, #0f172a 0%, #0f766e 100%);
            box-shadow: 0 20px 32px -24px rgba(15, 118, 110, 0.44);
        }

        .role-pill {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 0.24rem 0.62rem;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.01em;
        }

        .role-pill-admin {
            background: #fff1f2;
            color: #be123c;
        }

        .role-pill-guest {
            background: #fffbeb;
            color: #b45309;
        }

        .role-pill-user {
            background: #ecfdf5;
            color: #0f766e;
        }

        .status-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid rgba(153, 246, 228, 0.5);
            border-radius: 0.9rem;
            background: rgba(236, 253, 245, 0.8);
            padding: 0.45rem 0.8rem;
        }

        .profile-meta-row {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding-bottom: 0.95rem;
            border-bottom: 1px solid rgba(241, 245, 249, 0.95);
        }

        .profile-meta-row:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }

        .profile-meta-row dt {
            font-size: 0.9rem;
            color: #64748b;
        }

        .profile-meta-row dd {
            margin: 0;
            text-align: right;
            font-size: 0.92rem;
            font-weight: 700;
            color: #0f172a;
        }

        .form-label {
            display: inline-block;
            margin-bottom: 0.7rem;
            font-size: 0.9rem;
            font-weight: 700;
            color: #0f172a;
        }

        .form-input {
            width: 100%;
            border: 1px solid #dbe3ee;
            border-radius: 1rem;
            background: rgba(248, 250, 252, 0.72);
            padding: 0.95rem 1rem;
            font-size: 0.95rem;
            color: #0f172a;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        .form-input:focus {
            border-color: rgba(15, 118, 110, 0.38);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.1);
        }

        .form-input-error {
            border-color: rgba(244, 63, 94, 0.44);
            background: rgba(255, 241, 242, 0.7);
        }

        .action-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.95rem;
            padding: 0.82rem 1.05rem;
            font-size: 0.92rem;
            font-weight: 700;
            transition: transform 0.2s ease, background-color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease, color 0.2s ease;
        }

        .action-button:hover {
            transform: translateY(-1px);
        }

        .action-button-dark {
            border: 1px solid rgba(15, 23, 42, 0.08);
            background: linear-gradient(135deg, #0f172a 0%, #0f766e 100%);
            color: #ffffff;
            box-shadow: 0 22px 34px -26px rgba(15, 118, 110, 0.45);
        }

        .action-button-warm {
            border: 1px solid rgba(253, 230, 138, 0.72);
            background: rgba(255, 251, 235, 0.94);
            color: #b45309;
        }

        .action-button-danger {
            border: 1px solid rgba(253, 205, 211, 0.92);
            background: rgba(255, 241, 242, 0.95);
            color: #e11d48;
        }

        @media (max-width: 640px) {
            .profile-meta-row {
                flex-direction: column;
                gap: 0.35rem;
            }

            .profile-meta-row dd {
                text-align: left;
            }
        }
    </style>
</x-layouts.app>
