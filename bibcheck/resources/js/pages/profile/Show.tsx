import { Transition } from '@headlessui/react';
import { Head, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, User } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Профиль',
        href: '/profile',
    },
];

interface ProfileProps {
    user: User & {
        guest_session?: {
            expires_at: string;
            remaining_seconds: number;
            remaining_formatted: string;
            is_expiring_soon: boolean;
        } | null;
    };
}

export default function ProfileShow({ user }: ProfileProps) {
    const { flash } = usePage().props as any;
    const [showApiKey, setShowApiKey] = useState(false);
    const [apiKeyValue, setApiKeyValue] = useState(user.openalex_api_key || '');

    const nameForm = useForm({
        name: user.name,
    });

    const apiKeyForm = useForm({
        openalex_api_key: apiKeyValue,
    });

    const passwordForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const handleApiKeyUpdate = () => {
        apiKeyForm.put('/profile/api-key', {
            preserveScroll: true,
            onSuccess: () => {
                setApiKeyValue(apiKeyForm.data.openalex_api_key);
            },
        });
    };

    const handleApiKeyDelete = () => {
        apiKeyForm.delete('/profile/api-key', {
            preserveScroll: true,
            onSuccess: () => {
                setApiKeyValue('');
                setShowApiKey(false);
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Профиль" />

            <div className="mx-auto max-w-4xl space-y-6 py-8">
                <h1 className="text-3xl font-bold">Профиль пользователя</h1>

                {/* Flash Message */}
                {flash?.success && (
                    <div className="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                        {flash.success}
                    </div>
                )}

                {/* Guest Session Warning */}
                {user.is_guest && user.guest_session && (
                    <Card className={user.guest_session.is_expiring_soon ? 'border-yellow-500' : ''}>
                        <CardHeader>
                            <CardTitle className="text-lg">
                                {user.guest_session.is_expiring_soon
                                    ? '⚠️ Гостевая сессия скоро истечет'
                                    : '👋 Гостевой режим'}
                            </CardTitle>
                            <CardDescription>
                                {user.is_guest && (
                                    <div className="space-y-2">
                                        <p>
                                            Время действия сессии:{' '}
                                            {user.guest_session.remaining_formatted}
                                        </p>
                                        {user.guest_session.is_expiring_soon && (
                                            <p className="text-yellow-600">
                                                Сессия истекает менее чем через час. 
                                                Зарегистрируйтесь, чтобы сохранить данные.
                                            </p>
                                        )}
                                    </div>
                                )}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="flex gap-2">
                            <form
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    nameForm.post('/profile/guest/extend');
                                }}
                            >
                                <Button type="submit" variant="outline">
                                    Продлить сессию
                                </Button>
                            </form>
                            <a href="/register">
                                <Button>Зарегистрироваться</Button>
                            </a>
                        </CardContent>
                    </Card>
                )}

                {/* Profile Information */}
                <Card>
                    <CardHeader>
                        <CardTitle>Информация профиля</CardTitle>
                        <CardDescription>
                            Обновите имя и настройки профиля
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form
                            onSubmit={(e) => {
                                e.preventDefault();
                                nameForm.put('/profile/name');
                            }}
                            className="space-y-4"
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="name">Имя</Label>
                                <Input
                                    id="name"
                                    {...nameForm.register('name')}
                                    disabled={nameForm.processing}
                                />
                                <InputError message={nameForm.errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label>Email</Label>
                                <Input value={user.email} disabled />
                                <p className="text-xs text-muted-foreground">
                                    Email нельзя изменить
                                </p>
                            </div>

                            <div className="grid gap-2">
                                <Label>Роль</Label>
                                <Input
                                    value={
                                        user.role === 'admin'
                                            ? 'Администратор'
                                            : user.role === 'user'
                                              ? 'Пользователь'
                                              : 'Гость'
                                    }
                                    disabled
                                />
                            </div>

                            <div className="flex items-center gap-4">
                                <Button
                                    type="submit"
                                    disabled={nameForm.processing}
                                >
                                    Сохранить
                                </Button>
                                <Transition
                                    show={nameForm.recentlySuccessful}
                                    enter="transition ease-in-out"
                                    enterFrom="opacity-0"
                                    leave="transition ease-in-out"
                                    leaveTo="opacity-0"
                                >
                                    <p className="text-sm text-green-600">
                                        Сохранено
                                    </p>
                                </Transition>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* API Key Section */}
                <Card>
                    <CardHeader>
                        <CardTitle>API ключ OpenAlex</CardTitle>
                        <CardDescription>
                            Управляйте своим API ключом для сервиса OpenAlex
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {!user.openalex_api_key ? (
                            <div className="space-y-4">
                                <p className="text-sm text-muted-foreground">
                                    У вас нет сохраненного API ключа. Введите ключ ниже.
                                </p>
                                <form
                                    onSubmit={(e) => {
                                        e.preventDefault();
                                        handleApiKeyUpdate();
                                    }}
                                    className="space-y-4"
                                >
                                    <div className="grid gap-2">
                                        <Label htmlFor="api_key">
                                            API ключ OpenAlex
                                        </Label>
                                        <Input
                                            id="api_key"
                                            type="password"
                                            placeholder="Введите ваш API ключ"
                                            {...apiKeyForm.register(
                                                'openalex_api_key',
                                            )}
                                            disabled={apiKeyForm.processing}
                                        />
                                        <InputError
                                            message={
                                                apiKeyForm.errors.openalex_api_key
                                            }
                                        />
                                    </div>
                                    <Button
                                        type="submit"
                                        disabled={apiKeyForm.processing}
                                    >
                                        Сохранить ключ
                                    </Button>
                                </form>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="api_key">
                                        Ваш API ключ
                                    </Label>
                                    <div className="flex gap-2">
                                        <Input
                                            id="api_key"
                                            type={showApiKey ? 'text' : 'password'}
                                            value={user.openalex_api_key}
                                            readOnly
                                        />
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() =>
                                                setShowApiKey(!showApiKey)
                                            }
                                        >
                                            {showApiKey ? 'Скрыть' : 'Показать'}
                                        </Button>
                                    </div>
                                </div>
                                <div className="flex gap-2">
                                    <Button
                                        variant="destructive"
                                        onClick={handleApiKeyDelete}
                                        disabled={apiKeyForm.processing}
                                    >
                                        Удалить ключ
                                    </Button>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Password Change (not for guests) */}
                {!user.is_guest && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Изменить пароль</CardTitle>
                            <CardDescription>
                                Обновите свой пароль для безопасности
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    passwordForm.put('/profile/password');
                                }}
                                className="space-y-4"
                            >
                                <div className="grid gap-2">
                                    <Label htmlFor="current_password">
                                        Текущий пароль
                                    </Label>
                                    <Input
                                        id="current_password"
                                        type="password"
                                        {...passwordForm.register(
                                            'current_password',
                                        )}
                                        disabled={passwordForm.processing}
                                    />
                                    <InputError
                                        message={
                                            passwordForm.errors.current_password
                                        }
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="password">Новый пароль</Label>
                                    <Input
                                        id="password"
                                        type="password"
                                        {...passwordForm.register('password')}
                                        disabled={passwordForm.processing}
                                    />
                                    <InputError
                                        message={passwordForm.errors.password}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="password_confirmation">
                                        Подтверждение пароля
                                    </Label>
                                    <Input
                                        id="password_confirmation"
                                        type="password"
                                        {...passwordForm.register(
                                            'password_confirmation',
                                        )}
                                        disabled={passwordForm.processing}
                                    />
                                    <InputError
                                        message={
                                            passwordForm.errors.password_confirmation
                                        }
                                    />
                                </div>

                                <div className="flex items-center gap-4">
                                    <Button
                                        type="submit"
                                        disabled={passwordForm.processing}
                                    >
                                        Изменить пароль
                                    </Button>
                                    <Transition
                                        show={passwordForm.recentlySuccessful}
                                        enter="transition ease-in-out"
                                        enterFrom="opacity-0"
                                        leave="transition ease-in-out"
                                        leaveTo="opacity-0"
                                    >
                                        <p className="text-sm text-green-600">
                                            Пароль изменен
                                        </p>
                                    </Transition>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
