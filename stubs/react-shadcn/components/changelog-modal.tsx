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
import { useEffect, useState } from 'react';

interface Changelog {
    slug: string;
    version: string;
    title: string;
    html_content: string;
    published_at: string;
}

interface Props {
    changelog: Changelog | null;
}

export default function ChangelogModal({ changelog }: Props) {
    const [open, setOpen] = useState(false);

    useEffect(() => {
        if (changelog) {
            setOpen(true);
        }
    }, [changelog]);

    if (!changelog) return null;

    const handleDismiss = () => {
        setOpen(false);
        router.post(`/changelogs/${changelog.slug}/dismiss-modal`, {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleViewFull = () => {
        setOpen(false);
        router.post(`/changelogs/${changelog.slug}/dismiss-modal`, {}, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                router.get(`/changelogs/${changelog.slug}`);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={(isOpen) => {
            if (!isOpen) handleDismiss();
        }}>
            <DialogContent className="max-h-[80vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-3">
                        {changelog.title}
                        {changelog.version && (
                            <span className="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary">
                                v{changelog.version}
                            </span>
                        )}
                    </DialogTitle>
                    <DialogDescription>
                        {changelog.published_at}
                    </DialogDescription>
                </DialogHeader>

                <div
                    className="prose prose-sm max-w-none dark:prose-invert"
                    dangerouslySetInnerHTML={{ __html: changelog.html_content }}
                />

                <DialogFooter>
                    <Button variant="outline" onClick={handleDismiss}>
                        Schliessen
                    </Button>
                    <Button onClick={handleViewFull}>
                        Alle Updates anzeigen
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
