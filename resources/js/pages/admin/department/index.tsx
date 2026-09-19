import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Админка',
        href: '/admin',
    },
    {
        title: 'Кафедра',
        href: '/admin/department',
    },
];

type Requirement = {
    id: number;
    course_number: number;
    min_total_quantity: number;
    min_foreign_lang: number;
    min_current_periodicals: number;
    min_21st_century: number;
};

type TypeField = {
    id: number;
    name_field: string;
};

type EntryType = {
    id: number;
    name_type_entry: string;
    fields: TypeField[];
};

type Props = {
    requirements: Requirement[];
    types: EntryType[];
};

export default function DepartmentIndex({ requirements, types }: Props) {
    const initialReq = Object.fromEntries(
        requirements.map((requirement) => [
            requirement.id,
            {
                min_total_quantity: requirement.min_total_quantity,
                min_foreign_lang: requirement.min_foreign_lang,
                min_current_periodicals: requirement.min_current_periodicals,
                min_21st_century: requirement.min_21st_century,
            },
        ]),
    );

    const form = useForm({
        req: initialReq,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Критерии кафедры" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <section className="rounded-3xl border border-border bg-card p-6 shadow-sm">
                    <div className="text-xs font-semibold uppercase tracking-[0.14em] text-muted-foreground">
                        Admin
                    </div>
                    <h1 className="mt-2 text-3xl font-bold text-foreground">
                        Критерии кафедры и ГОСТ
                    </h1>
                </section>

                <section className="rounded-2xl border border-border bg-card p-6 shadow-sm">
                    <h2 className="text-xl font-semibold text-foreground">
                        Минимальные требования по курсам
                    </h2>

                    <form
                        className="mt-6 space-y-4"
                        onSubmit={(event) => {
                            event.preventDefault();
                            form.post('/admin/department/requirements');
                        }}
                    >
                        <div className="overflow-x-auto rounded-2xl border border-border">
                            <table className="min-w-full text-left text-sm">
                                <thead className="bg-muted/40 text-muted-foreground">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">Курс</th>
                                        <th className="px-4 py-3 font-medium">Всего источников</th>
                                        <th className="px-4 py-3 font-medium">Иностр. яз.</th>
                                        <th className="px-4 py-3 font-medium">Периодика</th>
                                        <th className="px-4 py-3 font-medium">XXI век</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {requirements.map((requirement) => (
                                        <tr key={requirement.id} className="border-t border-border">
                                            <td className="px-4 py-3 font-semibold text-foreground">
                                                {requirement.course_number}
                                            </td>
                                            <td className="px-4 py-3">
                                                <input
                                                    type="number"
                                                    value={form.data.req[requirement.id].min_total_quantity}
                                                    onChange={(event) =>
                                                        form.setData('req', {
                                                            ...form.data.req,
                                                            [requirement.id]: {
                                                                ...form.data.req[requirement.id],
                                                                min_total_quantity: Number(event.target.value),
                                                            },
                                                        })
                                                    }
                                                    className="w-full rounded-xl border border-input bg-background px-3 py-2"
                                                />
                                            </td>
                                            <td className="px-4 py-3">
                                                <input
                                                    type="number"
                                                    value={form.data.req[requirement.id].min_foreign_lang}
                                                    onChange={(event) =>
                                                        form.setData('req', {
                                                            ...form.data.req,
                                                            [requirement.id]: {
                                                                ...form.data.req[requirement.id],
                                                                min_foreign_lang: Number(event.target.value),
                                                            },
                                                        })
                                                    }
                                                    className="w-full rounded-xl border border-input bg-background px-3 py-2"
                                                />
                                            </td>
                                            <td className="px-4 py-3">
                                                <input
                                                    type="number"
                                                    value={
                                                        form.data.req[requirement.id].min_current_periodicals
                                                    }
                                                    onChange={(event) =>
                                                        form.setData('req', {
                                                            ...form.data.req,
                                                            [requirement.id]: {
                                                                ...form.data.req[requirement.id],
                                                                min_current_periodicals: Number(event.target.value),
                                                            },
                                                        })
                                                    }
                                                    className="w-full rounded-xl border border-input bg-background px-3 py-2"
                                                />
                                            </td>
                                            <td className="px-4 py-3">
                                                <input
                                                    type="number"
                                                    value={form.data.req[requirement.id].min_21st_century}
                                                    onChange={(event) =>
                                                        form.setData('req', {
                                                            ...form.data.req,
                                                            [requirement.id]: {
                                                                ...form.data.req[requirement.id],
                                                                min_21st_century: Number(event.target.value),
                                                            },
                                                        })
                                                    }
                                                    className="w-full rounded-xl border border-input bg-background px-3 py-2"
                                                />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <button
                            type="submit"
                            disabled={form.processing}
                            className="inline-flex items-center justify-center rounded-xl bg-foreground px-4 py-2 text-sm font-semibold text-background transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-70"
                        >
                            Сохранить настройки курсов
                        </button>
                    </form>
                </section>

                <section className="rounded-2xl border border-border bg-card p-6 shadow-sm">
                    <h2 className="text-xl font-semibold text-foreground">
                        Обязательные поля по типам записей
                    </h2>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Изменить состав обязательных полей можно во вкладке конфигуратора BibTeX.
                    </p>

                    <div className="mt-6 grid gap-4 md:grid-cols-2">
                        {types.map((type) => (
                            <div
                                key={type.id}
                                className="rounded-2xl border border-border bg-background p-4"
                            >
                                <div className="flex items-center justify-between gap-3">
                                    <h3 className="font-semibold uppercase text-foreground">
                                        {type.name_type_entry}
                                    </h3>
                                    <span className="text-xs text-muted-foreground">ID: {type.id}</span>
                                </div>

                                <div className="mt-4 flex flex-wrap gap-2">
                                    {type.fields.length ? (
                                        type.fields.map((field) => (
                                            <span
                                                key={field.id}
                                                className="rounded-full border border-border bg-card px-3 py-1 text-xs font-medium text-foreground"
                                            >
                                                {field.name_field}
                                            </span>
                                        ))
                                    ) : (
                                        <span className="text-sm text-muted-foreground">
                                            Поля не назначены
                                        </span>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}
