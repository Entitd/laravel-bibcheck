<x-layouts.app>
    @section('breadcrumb', 'Вход')

    <div class="guest-entry-page min-h-[80vh]">
        <div class="guest-entry-shell mx-auto flex min-h-[80vh] max-w-3xl items-center justify-center py-8">
            <section class="guest-card w-full">
                <div class="guest-card-head">
                    <div class="guest-icon">B</div>
                    <div class="guest-kicker">BIBCHECK</div>
                </div>

                <h1 class="guest-title">Выберите способ входа</h1>
                <p class="guest-subtitle">
                    Гостевой доступ подойдёт для быстрого старта. Аккаунт нужен, если вы хотите сохранять историю и настройки.
                </p>

                <div class="guest-option-card">
                    <div class="guest-option-label">Быстрый старт</div>
                    <div class="guest-option-title">Войти как гость</div>
                    <div class="guest-option-note">Сессия действует 24 часа.</div>

                    <form action="{{ route('guest.login.submit') }}" method="POST">
                        @csrf
                        <button type="submit" class="guest-primary-button w-full">
                            Продолжить как гость
                        </button>
                    </form>
                </div>

                <div class="guest-actions">
                    <a href="{{ route('register') }}" class="guest-secondary-button guest-secondary-button-dark">
                        Создать аккаунт
                    </a>

                    <a href="{{ route('login') }}" class="guest-secondary-button guest-secondary-button-soft">
                        Войти в аккаунт
                    </a>
                </div>
            </section>
        </div>
    </div>

    <style>
        .guest-entry-page {
            position: relative;
        }

        .guest-entry-shell {
            position: relative;
            z-index: 1;
        }

        .guest-card {
            border: 1px solid rgba(226, 232, 240, 0.92);
            border-radius: 1.9rem;
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(16px);
            box-shadow: 0 28px 52px -42px rgba(15, 23, 42, 0.24);
            padding: 1.6rem;
        }

        .guest-card-head {
            display: flex;
            align-items: center;
            gap: 0.9rem;
            margin-bottom: 1rem;
        }

        .guest-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 3rem;
            height: 3rem;
            border-radius: 1rem;
            background: linear-gradient(135deg, #0f172a 0%, #0f766e 100%);
            color: #ffffff;
            font-size: 1rem;
            font-weight: 800;
            box-shadow: 0 22px 34px -24px rgba(15, 118, 110, 0.42);
        }

        .guest-kicker,
        .guest-option-label {
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #94a3b8;
        }

        .guest-title {
            margin: 0;
            font-size: clamp(2rem, 5vw, 3.2rem);
            line-height: 0.98;
            letter-spacing: -0.05em;
            color: #0f172a;
        }

        .guest-subtitle {
            margin: 0.95rem 0 1.4rem;
            max-width: 34rem;
            font-size: 0.98rem;
            line-height: 1.7;
            color: #475569;
        }

        .guest-option-card {
            border: 1px solid rgba(226, 232, 240, 0.92);
            border-radius: 1.4rem;
            background: linear-gradient(180deg, rgba(248, 250, 252, 0.92) 0%, rgba(255, 255, 255, 0.98) 100%);
            padding: 1.2rem;
        }

        .guest-option-title {
            margin-top: 0.55rem;
            font-size: 1.08rem;
            font-weight: 700;
            color: #0f172a;
        }

        .guest-option-note {
            margin: 0.5rem 0 1rem;
            font-size: 0.9rem;
            color: #64748b;
        }

        .guest-actions {
            display: grid;
            gap: 0.9rem;
            margin-top: 1rem;
        }

        .guest-primary-button,
        .guest-secondary-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            border-radius: 1rem;
            padding: 0.95rem 1.1rem;
            font-size: 0.94rem;
            font-weight: 700;
            text-align: center;
            transition: transform 0.2s ease, background-color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease, color 0.2s ease;
        }

        .guest-primary-button:hover,
        .guest-secondary-button:hover {
            transform: translateY(-1px);
        }

        .guest-primary-button {
            border: 1px solid rgba(15, 23, 42, 0.08);
            background: linear-gradient(135deg, #0f172a 0%, #0f766e 100%);
            color: #ffffff;
            box-shadow: 0 24px 40px -28px rgba(15, 118, 110, 0.48);
        }

        .guest-secondary-button-dark {
            border: 1px solid rgba(15, 23, 42, 0.08);
            background: rgba(255, 255, 255, 0.92);
            color: #0f172a;
        }

        .guest-secondary-button-dark:hover {
            border-color: rgba(15, 118, 110, 0.2);
            background: rgba(240, 253, 250, 0.96);
        }

        .guest-secondary-button-soft {
            border: 1px solid rgba(226, 232, 240, 0.95);
            background: rgba(248, 250, 252, 0.94);
            color: #475569;
        }

        .guest-secondary-button-soft:hover {
            background: rgba(255, 255, 255, 0.98);
            color: #0f172a;
        }

        @media (max-width: 640px) {
            .guest-card {
                padding: 1.1rem;
                border-radius: 1.45rem;
            }

            .guest-title {
                font-size: 2.1rem;
            }
        }
    </style>
</x-layouts.app>
