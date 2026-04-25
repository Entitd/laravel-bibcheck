import AppLogoIcon from '@/components/app-logo-icon';
import {FileText} from 'lucide-react';

export default function AppLogo() {
    return (
        <>
            {/* <div className="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
                <AppLogoIcon className="size-5 fill-current text-white dark:text-black" />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">
                    Laravel Starter Kit
                </span>
            </div> */}

<div className="flex items-center gap-3">
                            <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-foreground text-background">
                                <FileText className="h-5 w-5" />
                            </div>
                            <div>
                                <div className="text-sm font-semibold text-foreground">BIBCHECK</div>
                                <div className="text-xs text-muted-foreground">Редактор и проверка</div>
                            </div>
                        </div>
        </>
    );
}
