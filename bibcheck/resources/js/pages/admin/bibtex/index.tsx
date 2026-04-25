import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import InputError from '@/components/input-error';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Админка',
        href: '/admin',
    },
    {
        title: 'BibTeX',
        href: '/admin/bibtex',
    },
];

type Field = {
    id: number;
    name_field: string;
};

type EntryType = {
    id: number;
    name_type_entry: string;
    fields: Field[];
};

type Props = {
    types: EntryType[];
    fields: Field[];
    allFields: Field[];
};

export default function BibtexIndex({ types, fields, allFields }: Props) {
    const typeForm = useForm<{
        name_type_entry: string;
        field_ids: number[];
    }>({
        name_type_entry: '',
        field_ids: [],
    });

    const fieldForm = useForm({
        name_field: '',
    });

    const toggleField = (fieldId: number) => {
        typeForm.setData(
            'field_ids',
            typeForm.data.field_ids.includes(fieldId)
                ? typeForm.data.field_ids.filter((id) => id !== fieldId)
                : [...typeForm.data.field_ids, fieldId],
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Конфигуратор BibTeX" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <section className="rounded-3xl border border-border bg-card p-6 shadow-sm">
                    <div className="text-xs font-semibold uppercase tracking-[0.14em] text-muted-foreground">
                        Admin
                    </div>
                    <h1 className="mt-2 text-3xl font-bold text-foreground">
                        Конфигуратор структуры BibTeX
                    </h1>
                </section>

                <div className="grid gap-6 lg:grid-cols-[360px_1fr]">
                    <div className="space-y-6">
                        <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                            <h2 className="text-lg font-semibold text-foreground">Создать новый тип</h2>
                            <form
                                className="mt-4 space-y-4"
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    typeForm.post('/admin/bibtex', {
                                        preserveScroll: true,
                                        onSuccess: () => typeForm.reset(),
                                    });
                                }}
                            >
                                <div>
                                    <label
                                        htmlFor="name_type_entry"
                                        className="mb-2 block text-sm font-medium text-foreground"
                                    >
                                        Название (slug)
                                    </label>
                                    <input
                                        id="name_type_entry"
                                        value={typeForm.data.name_type_entry}
                                        onChange={(event) =>
                                            typeForm.setData('name_type_entry', event.target.value)
                                        }
                                        className="w-full rounded-xl border border-input bg-background px-3 py-2"
                                        placeholder="например: thesis"
                                    />
                                    <InputError className="mt-2" message={typeForm.errors.name_type_entry} />
                                </div>

                                <div>
                                    <div className="mb-2 text-sm font-medium text-foreground">
                                        Обязательные поля
                                    </div>
                                    <div className="grid max-h-64 grid-cols-2 gap-2 overflow-y-auto rounded-xl border border-border bg-background p-3">
                                        {allFields.map((field) => (
                                            <label
                                                key={field.id}
                                                className="flex items-center gap-2 text-sm text-foreground"
                                            >
                                                <input
                                                    type="checkbox"
                                                    checked={typeForm.data.field_ids.includes(field.id)}
                                                    onChange={() => toggleField(field.id)}
                                                />
                                                <span>{field.name_field}</span>
                                            </label>
                                        ))}
                                    </div>
                                    <InputError className="mt-2" message={typeForm.errors.field_ids} />
                                </div>

                                <button
                                    type="submit"
                                    disabled={typeForm.processing}
                                    className="inline-flex w-full items-center justify-center rounded-xl bg-foreground px-4 py-2 text-sm font-semibold text-background transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-70"
                                >
                                    Создать тип
                                </button>
                            </form>
                        </section>

                        <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                            <h2 className="text-lg font-semibold text-foreground">Справочник полей</h2>

                            <form
                                className="mt-4 flex gap-2"
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    fieldForm.post('/admin/fields', {
                                        preserveScroll: true,
                                        onSuccess: () => fieldForm.reset(),
                                    });
                                }}
                            >
                                <input
                                    value={fieldForm.data.name_field}
                                    onChange={(event) =>
                                        fieldForm.setData('name_field', event.target.value)
                                    }
                                    className="flex-1 rounded-xl border border-input bg-background px-3 py-2 text-sm"
                                    placeholder="doi, isbn..."
                                />
                                <button
                                    type="submit"
                                    disabled={fieldForm.processing}
                                    className="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-600 text-lg font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-70"
                                >
                                    +
                                </button>
                            </form>
                            <InputError className="mt-2" message={fieldForm.errors.name_field} />

                            <div className="mt-5 flex flex-wrap gap-2">
                                {fields.map((field) => (
                                    <div
                                        key={field.id}
                                        className="flex items-center gap-2 rounded-full border border-border bg-background px-3 py-1.5 text-sm"
                                    >
                                        <span>{field.name_field}</span>
                                        <button
                                            type="button"
                                            onClick={() => router.delete(`/admin/fields/${field.id}`)}
                                            className="text-muted-foreground transition hover:text-rose-600"
                                        >
                                            x
                                        </button>
                                    </div>
                                ))}
                            </div>
                        </section>
                    </div>

                    <section className="rounded-2xl border border-border bg-card p-5 shadow-sm">
                        <h2 className="text-xl font-semibold text-foreground">
                            Существующие типы записей
                        </h2>

                        <div className="mt-5 grid gap-4 md:grid-cols-2">
                            {types.map((type) => (
                                <div
                                    key={type.id}
                                    className="flex flex-col justify-between rounded-2xl border border-border bg-background p-5"
                                >
                                    <div>
                                        <h3 className="text-lg font-semibold uppercase text-foreground">
                                            {type.name_type_entry}
                                        </h3>
                                        <div className="mt-4 flex flex-wrap gap-2">
                                            {type.fields.map((field) => (
                                                <span
                                                    key={field.id}
                                                    className="rounded-full border border-border bg-card px-3 py-1 text-xs font-medium text-foreground"
                                                >
                                                    {field.name_field}
                                                </span>
                                            ))}
                                        </div>
                                    </div>

                                    <button
                                        type="button"
                                        onClick={() => router.delete(`/admin/bibtex/${type.id}`)}
                                        className="mt-5 self-start text-sm font-semibold text-rose-600 transition hover:text-rose-700"
                                    >
                                        Удалить тип
                                    </button>
                                </div>
                            ))}
                        </div>
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}
