import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, BookCopy, Files, LibraryBig } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Админка',
        href: '/admin',
    },
];

type DashboardProps = {
    stats: {
        files: number;
        types: number;
        errors: number;
        fields: number;
    };
};

export default function AdminDashboard({ stats }: DashboardProps) {
    const cards = [
        { label: 'Загружено файлов', value: stats.files, icon: Files },
        { label: 'Типов записей', value: stats.types, icon: BookCopy },
        { label: 'Всего ошибок', value: stats.errors, icon: AlertTriangle },
        { label: 'Полей в базе', value: stats.fields, icon: LibraryBig },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Админка" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <section className="rounded-3xl border border-border bg-card p-6 shadow-sm">
                    <div className="text-xs font-semibold uppercase tracking-[0.14em] text-muted-foreground">
                        Admin
                    </div>
                    <h1 className="mt-2 text-3xl font-bold text-foreground">
                        Обзор системы BibCheck
                    </h1>
                    <p className="mt-3 max-w-2xl text-sm leading-7 text-muted-foreground">
                        Короткая сводка по данным системы и быстрые переходы к конфигурации.
                    </p>
                </section>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {cards.map(({ label, value, icon: Icon }) => (
                        <div
                            key={label}
                            className="rounded-2xl border border-border bg-card p-5 shadow-sm"
                        >
                            <div className="flex items-center justify-between">
                                <div className="text-sm text-muted-foreground">{label}</div>
                                <Icon className="h-4 w-4 text-muted-foreground" />
                            </div>
                            <div className="mt-4 text-3xl font-bold text-foreground">{value}</div>
                        </div>
                    ))}
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <section className="rounded-2xl border border-border bg-card p-6 shadow-sm">
                        <h2 className="text-lg font-semibold text-foreground">
                            Последние загрузки
                        </h2>
                        <p className="mt-3 text-sm leading-7 text-muted-foreground">
                            Здесь можно добавить расширенную аналитику по последним загруженным `.bib` файлам.
                        </p>
                    </section>

                    <section className="rounded-2xl border border-border bg-card p-6 shadow-sm">
                        <h2 className="text-lg font-semibold text-foreground">
                            Быстрые действия
                        </h2>
                        <div className="mt-4 grid gap-3">
                            <Link
                                href="/admin/bibtex"
                                className="rounded-xl border border-border bg-background px-4 py-3 text-sm font-semibold text-foreground transition hover:bg-accent"
                            >
                                Настроить структуру полей
                            </Link>
                            <Link
                                href="/admin/department"
                                className="rounded-xl border border-border bg-background px-4 py-3 text-sm font-semibold text-foreground transition hover:bg-accent"
                            >
                                Изменить критерии кафедры
                            </Link>
                        </div>
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}
