import { usePage } from '@inertiajs/react';
import { ChevronsUpDown } from 'lucide-react';
import { useEffect, useState } from 'react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { UserInfo } from '@/components/user-info';
import { UserMenuContent } from '@/components/user-menu-content';
import { useIsMobile } from '@/hooks/use-mobile';
import type { User } from '@/types';

type GuestSession = {
    expires_at: string;
    remaining_seconds: number;
    remaining_formatted: string;
    is_expiring_soon: boolean;
};

type PageProps = {
    auth: {
        user: User;
    };
    guestSession?: GuestSession | null;
};

function formatGuestTime(totalSeconds: number) {
    const seconds = Math.max(0, Math.floor(totalSeconds));
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);

    if (hours > 0) {
        return `${hours} ч ${minutes} мин`;
    }

    if (minutes > 0) {
        return `${minutes} мин`;
    }

    return 'меньше минуты';
}

export function NavUser() {
    const { auth, guestSession } = usePage<PageProps>().props;
    const { state } = useSidebar();
    const isMobile = useIsMobile();
    const [remainingSeconds, setRemainingSeconds] = useState(
        guestSession?.remaining_seconds ?? 0,
    );

    useEffect(() => {
        setRemainingSeconds(guestSession?.remaining_seconds ?? 0);
    }, [guestSession?.remaining_seconds]);

    useEffect(() => {
        if (!auth.user.is_guest || !guestSession?.remaining_seconds) {
            return;
        }

        const interval = window.setInterval(() => {
            setRemainingSeconds((seconds) => Math.max(0, seconds - 1));
        }, 1000);

        return () => window.clearInterval(interval);
    }, [auth.user.is_guest, guestSession?.remaining_seconds]);

    const guestSessionDescription =
        auth.user.is_guest && remainingSeconds > 0
            ? `Действителен еще ${formatGuestTime(remainingSeconds)}`
            : null;

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton
                            size="lg"
                            className="group text-sidebar-accent-foreground data-[state=open]:bg-sidebar-accent"
                            data-test="sidebar-menu-button"
                        >
                            <UserInfo
                                user={auth.user}
                                description={guestSessionDescription}
                            />
                            <ChevronsUpDown className="ml-auto size-4" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                        align="end"
                        side={
                            isMobile
                                ? 'bottom'
                                : state === 'collapsed'
                                  ? 'left'
                                  : 'bottom'
                        }
                    >
                        <UserMenuContent
                            user={auth.user}
                            guestSessionDescription={guestSessionDescription}
                        />
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
