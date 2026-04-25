import { Link, usePage } from '@inertiajs/react';
import { BookOpen, FolderGit2, LayoutGrid, ListChecks, Settings, TextSearch } from 'lucide-react';
import { useMemo, useState } from 'react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarSeparator,
} from '@/components/ui/sidebar';
import type { NavItem, User } from '@/types';

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

type PageProps = {
    auth: {
        user: User;
    };
    recentChecks?: {
        id: number;
        filename: string;
        relative_time?: string;
    }[];
};

export function AppSidebar() {
    const { auth, recentChecks = [] } = usePage<PageProps>().props;
    const [query, setQuery] = useState('');
    const mainNavItems: NavItem[] = useMemo(() => {
        const items: NavItem[] = [
            {
                title: 'Редактор',
                href: '/',
                icon: TextSearch,
            },
            {
                title: 'Профиль',
                href: '/profile',
                icon: Settings,
            },
        ];

        if (auth.user.role === 'admin') {
            items.push({
                title: 'Админка',
                href: '/admin',
                icon: LayoutGrid,
            });
        }

        return items;
    }, [auth.user.role]);

    const filteredChecks = recentChecks.filter((check) =>
        check.filename.toLowerCase().includes(query.toLowerCase()),
    );

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
                <SidebarSeparator />
                <SidebarGroup className="px-2 pt-0">
                    <SidebarGroupLabel>История</SidebarGroupLabel>
                    <div className="px-2 pb-2 group-data-[collapsible=icon]:hidden">
                        <input
                            value={query}
                            onChange={(event) => setQuery(event.target.value)}
                            placeholder="Найти проверку"
                            className="h-8 w-full rounded-md border border-input bg-background px-2 text-xs outline-none transition focus:border-ring"
                        />
                    </div>
                    <SidebarMenu>
                        {filteredChecks.length ? (
                            filteredChecks.map((check) => (
                                <SidebarMenuItem key={check.id}>
                                    <SidebarMenuButton
                                        asChild
                                        tooltip={{ children: check.filename }}
                                    >
                                        <Link href={`/check-history/${check.id}`}>
                                            <ListChecks />
                                            <span>{check.filename}</span>
                                        </Link>
                                    </SidebarMenuButton>
                                </SidebarMenuItem>
                            ))
                        ) : (
                            <div className="px-2 text-xs text-sidebar-foreground/70 group-data-[collapsible=icon]:hidden">
                                История пуста
                            </div>
                        )}
                    </SidebarMenu>
                </SidebarGroup>
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
