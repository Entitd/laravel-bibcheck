import { useForm } from '@inertiajs/react';
import React from 'react';

export default function Welcome() {
    // Инициализируем форму через Inertia
    const { data, setData, post, processing, errors } = useForm({
        bib_file: null as File | null,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        // Отправляем POST запрос на роут /upload-bib
        // Мы прописали этот путь в routes/web.php
        post('/upload-bib');
    };

    return (
        <div className="p-10">
            <h1 className="mb-5 text-2xl font-bold">Анализатор BIB-файлов</h1>

            <form onSubmit={submit} className="space-y-4">
                <div>
                    <input
                        type="file"
                        onChange={(e) =>
                            setData(
                                'bib_file',
                                e.target.files ? e.target.files[0] : null,
                            )
                        }
                        title={errors.bib_file}
                        className={`block w-full text-sm text-gray-500 file:mr-4 file:rounded-full file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100 ${errors.bib_file ? 'border-red-500 ring-1 ring-red-500' : ''}`}
                    />
                    {errors.bib_file && (
                        <div className="mt-2 text-sm text-red-500">
                            {errors.bib_file}
                        </div>
                    )}
                </div>

                <button
                    type="submit"
                    disabled={processing}
                    className="rounded-lg bg-blue-600 px-6 py-2 text-white hover:bg-blue-700 disabled:bg-gray-400"
                >
                    {processing ? 'Обработка...' : 'Проверить BIB-файл'}
                </button>
            </form>
        </div>
    );
}
