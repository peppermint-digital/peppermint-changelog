import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { router } from '@inertiajs/react';
import { FileText, Sparkles, X } from 'lucide-react';
import { useEffect, useState } from 'react';

interface Changelog {
    slug: string;
    version: string | null;
    title: string;
    html_content: string;
}

interface Props {
    changelog: Changelog | null;
}

export default function ChangelogModal({ changelog }: Props) {
    const [open, setOpen] = useState(false);
    const [dismissing, setDismissing] = useState(false);
    const [mounted, setMounted] = useState(false);

    useEffect(() => {
        const timer = setTimeout(() => {
            setMounted(true);
            if (changelog) setOpen(true);
        }, 0);
        return () => clearTimeout(timer);
    }, []);

    useEffect(() => {
        if (changelog && mounted) {
            setOpen(true);
        }
    }, [changelog, mounted]);

    const dismissModal = () => {
        if (!changelog) return;
        setDismissing(true);
        router.post(
            `/changelogs/${changelog.slug}/dismiss-modal`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    setOpen(false);
                    setDismissing(false);
                },
                onError: () => {
                    setDismissing(false);
                },
            },
        );
    };

    const readMore = () => {
        if (!changelog) return;
        setDismissing(true);
        router.visit(`/changelogs/${changelog.slug}`, {
            onSuccess: () => setOpen(false),
        });
    };

    const handleOpenChange = (isOpen: boolean) => {
        if (!isOpen && !dismissing) {
            dismissModal();
        }
    };

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent
                className="sm:max-w-lg"
                onEscapeKeyDown={(e) => e.preventDefault()}
                onPointerDownOutside={(e) => e.preventDefault()}
            >
                <DialogHeader>
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10">
                            <Sparkles className="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <div className="flex items-center gap-2">
                                {changelog?.version && (
                                    <Badge variant="outline">v{changelog.version}</Badge>
                                )}
                                <DialogTitle>{changelog?.title}</DialogTitle>
                            </div>
                            <DialogDescription>Neues Update verfügbar</DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <div className="max-h-80 overflow-y-auto">
                    <div
                        className="prose prose-sm max-w-none dark:prose-invert"
                        dangerouslySetInnerHTML={{ __html: changelog?.html_content ?? '' }}
                    />
                </div>

                <DialogFooter className="flex gap-2 sm:justify-between">
                    <Button variant="ghost" size="sm" disabled={dismissing} onClick={dismissModal}>
                        <X className="mr-2 h-4 w-4" />
                        Schließen
                    </Button>
                    <Button disabled={dismissing} onClick={readMore}>
                        <FileText className="mr-2 h-4 w-4" />
                        Vollständiger Changelog
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
