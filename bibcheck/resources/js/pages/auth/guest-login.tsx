import { Head, Link, useForm } from '@inertiajs/react';
import AuthLayout from '@/layouts/auth-layout';

export default function GuestLogin() {
    const form = useForm({});

    return (
        <AuthLayout
            title="Выберите способ входа"
            description="Гостевой доступ подойдёт для быстрого старта. Аккаунт нужен, если вы хотите сохранять историю и настройки."
        >
            <Head title="Гостевой вход" />

            <div className="space-y-4">
                <div className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <div className="text-xs font-semibold uppercase tracking-[0.14em] text-muted-foreground">
                        Быстрый старт
                    </div>
                    <div className="mt-2 text-lg font-semibold text-foreground">
                        Войти как гость
                    </div>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Сессия действует 24 часа.
                    </p>

                    <form
                        className="mt-4"
                        onSubmit={(event) => {
                            event.preventDefault();
                            form.post('/guest/login');
                        }}
                    >
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="inline-flex w-full items-center justify-center rounded-xl bg-foreground px-4 py-3 text-sm font-semibold text-background transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-70"
                        >
                            Продолжить как гость
                        </button>
                    </form>
                </div>

                <div className="grid gap-3">
                    <Link
                        href="/register"
                        className="inline-flex items-center justify-center rounded-xl border border-border bg-card px-4 py-3 text-sm font-semibold text-foreground transition hover:bg-accent"
                    >
                        Создать аккаунт
                    </Link>
                    <Link
                        href="/login"
                        className="inline-flex items-center justify-center rounded-xl border border-border bg-background px-4 py-3 text-sm font-semibold text-muted-foreground transition hover:bg-accent hover:text-foreground"
                    >
                        Войти в аккаунт
                    </Link>
                </div>
            </div>
        </AuthLayout>
    );
}
