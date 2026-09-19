import type { LucideIcon } from 'lucide-react';
import { Monitor, Moon, Sun } from 'lucide-react';
import type { HTMLAttributes } from 'react';
import type { Appearance } from '@/hooks/use-appearance';
import { useAppearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';

type AppearanceToggleVariant = 'default' | 'auth' | 'sidebar';

const tabs: { value: Appearance; icon: LucideIcon; label: string }[] = [
    { value: 'light', icon: Sun, label: 'Свет' },
    { value: 'dark', icon: Moon, label: 'Тьма' },
    { value: 'system', icon: Monitor, label: 'Авто' },
];

const activeIndexByAppearance: Record<Appearance, number> = {
    light: 0,
    dark: 1,
    system: 2,
};

export default function AppearanceToggleTab({
    className = '',
    variant = 'default',
    ...props
}: HTMLAttributes<HTMLDivElement> & { variant?: AppearanceToggleVariant }) {
    const { appearance, updateAppearance } = useAppearance();
    const activeIndex = activeIndexByAppearance[appearance];

    return (
        <div
            className={cn(
                'relative grid grid-cols-3 rounded-full border p-1 shadow-xs transition-colors',
                'border-border/70 bg-background/85 text-muted-foreground backdrop-blur',
                'dark:border-white/10 dark:bg-neutral-950/70',
                variant === 'auth' &&
                    'w-full max-w-[17rem] bg-muted/60 dark:bg-neutral-900/70',
                variant === 'sidebar' &&
                    'mx-1 mb-1 w-[calc(100%_-_0.5rem)] bg-muted/60 dark:bg-neutral-900/80',
                variant === 'default' && 'inline-grid min-w-[16rem]',
                className,
            )}
            {...props}
        >
            <span
                className={cn(
                    'pointer-events-none absolute top-1 bottom-1 left-1 w-[calc((100%_-_0.5rem)/3)] rounded-full shadow-sm transition-transform duration-300 ease-out',
                    'bg-white ring-1 ring-black/5 dark:bg-neutral-800 dark:ring-white/10',
                    activeIndex === 0 && 'translate-x-0',
                    activeIndex === 1 && 'translate-x-full',
                    activeIndex === 2 && 'translate-x-[200%]',
                )}
            />
            {tabs.map(({ value, icon: Icon, label }) => (
                <button
                    key={value}
                    type="button"
                    onClick={() => updateAppearance(value)}
                    className={cn(
                        'relative z-10 flex min-w-0 items-center justify-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-medium transition-colors',
                        appearance === value
                            ? 'text-foreground'
                            : 'hover:text-foreground',
                        variant === 'sidebar' && 'px-2 text-xs',
                    )}
                    aria-pressed={appearance === value}
                    title={label}
                >
                    <Icon className="size-4 shrink-0" />
                    <span className="truncate">{label}</span>
                </button>
            ))}
        </div>
    );
}
