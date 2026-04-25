import { Link, router } from '@inertiajs/react';
import { LogIn, LogOut, RefreshCw, Settings, UserPlus } from 'lucide-react';
import AppearanceToggleTab from '@/components/appearance-tabs';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { UserInfo } from '@/components/user-info';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';
import { logout } from '@/routes';
import type { User } from '@/types';

type Props = {
    user: User;
    guestSessionDescription?: string | null;
};

export function UserMenuContent({ user, guestSessionDescription }: Props) {
    const cleanup = useMobileNavigation();

    const handleLogout = () => {
        cleanup();
        router.flushAll();
    };

    const handleExtendGuestSession = () => {
        cleanup();
        router.post('/profile/guest/extend', {}, { preserveScroll: true });
    };

    return (
        <>
            <DropdownMenuLabel className="p-0 font-normal">
                <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                    <UserInfo
                        user={user}
                        showEmail={true}
                        description={guestSessionDescription}
                    />
                </div>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <AppearanceToggleTab variant="sidebar" />
            <DropdownMenuSeparator />
            {user.is_guest && (
                <>
                    <DropdownMenuGroup>
                        <DropdownMenuItem asChild>
                            <Link
                                className="block w-full cursor-pointer"
                                href="/profile/guest/register"
                                onClick={cleanup}
                            >
                                <UserPlus className="mr-2" />
                                Зарегистрироваться
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem asChild>
                            <Link
                                className="block w-full cursor-pointer"
                                href="/profile/guest/login"
                                onClick={cleanup}
                            >
                                <LogIn className="mr-2" />
                                Войти
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            className="cursor-pointer"
                            onClick={handleExtendGuestSession}
                        >
                            <RefreshCw className="mr-2" />
                            Обновить таймер
                        </DropdownMenuItem>
                    </DropdownMenuGroup>
                    <DropdownMenuSeparator />
                </>
            )}
            <DropdownMenuGroup>
                <DropdownMenuItem asChild>
                    <Link
                        className="block w-full cursor-pointer"
                        href="/profile"
                        prefetch
                        onClick={cleanup}
                    >
                        <Settings className="mr-2" />
                        Настройки
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuGroup>
            <DropdownMenuSeparator />
            <DropdownMenuItem asChild>
                <Link
                    className="block w-full cursor-pointer"
                    href={logout()}
                    as="button"
                    onClick={handleLogout}
                    data-test="logout-button"
                >
                    <LogOut className="mr-2" />
                    Выйти
                </Link>
            </DropdownMenuItem>
        </>
    );
}
