import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

interface Changelog {
    slug: string;
    version: string;
    title: string;
    content: string;
    html_content: string;
    is_markdown: boolean;
    published_at: string;
}

interface Props {
    changelog: Changelog;
}

export default function ChangelogShow({ changelog }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Changelog', href: '/changelogs' },
        { title: changelog.title, href: `/changelogs/${changelog.slug}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${changelog.title} - Changelog`} />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => router.get('/changelogs')}
                    >
                        <ArrowLeft className="h-5 w-5" />
                    </Button>
                    <div className="flex items-center gap-3">
                        <h1 className="text-2xl font-bold">{changelog.title}</h1>
                        {changelog.version && (
                            <Badge variant="secondary">v{changelog.version}</Badge>
                        )}
                    </div>
                </div>

                <p className="text-sm text-muted-foreground">{changelog.published_at}</p>

                <Card>
                    <CardContent className="pt-6">
                        <div
                            className="prose max-w-none dark:prose-invert"
                            dangerouslySetInnerHTML={{ __html: changelog.html_content }}
                        />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
