import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { BookOpen } from 'lucide-react';

interface Changelog {
    slug: string;
    version: string;
    title: string;
    html_content: string;
    published_at: string;
    is_read: boolean;
}

interface Props {
    changelogs: Changelog[];
}

export default function ChangelogIndex({ changelogs }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Changelog', href: '/changelogs' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Changelog" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div>
                    <h1 className="text-2xl font-bold">Changelog</h1>
                    <p className="text-muted-foreground">
                        Alle Updates und Neuigkeiten
                    </p>
                </div>

                {changelogs.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <BookOpen className="mb-4 h-12 w-12 text-muted-foreground" />
                            <h3 className="mb-2 text-lg font-semibold">
                                Noch keine Updates
                            </h3>
                            <p className="text-muted-foreground">
                                Hier werden zukuenftige Updates angezeigt.
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="space-y-4">
                        {changelogs.map((changelog) => (
                            <Link
                                key={changelog.slug}
                                href={`/changelogs/${changelog.slug}`}
                                className="block"
                            >
                                <Card className={`transition-colors hover:bg-accent ${!changelog.is_read ? 'border-primary/50' : ''}`}>
                                    <CardHeader>
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-3">
                                                <CardTitle className="text-lg">
                                                    {changelog.title}
                                                </CardTitle>
                                                {changelog.version && (
                                                    <Badge variant="secondary">
                                                        v{changelog.version}
                                                    </Badge>
                                                )}
                                                {!changelog.is_read && (
                                                    <Badge>Neu</Badge>
                                                )}
                                            </div>
                                            <span className="text-sm text-muted-foreground">
                                                {changelog.published_at}
                                            </span>
                                        </div>
                                    </CardHeader>
                                </Card>
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
