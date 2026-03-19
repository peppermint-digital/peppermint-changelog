import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { ArrowLeft, Edit } from 'lucide-react';

interface Reader {
    id: number;
    name: string;
    read_at: string;
    modal_dismissed: boolean;
}

interface Changelog {
    slug: string;
    version: string;
    title: string;
    html_content: string;
    published_at: string;
    show_modal: boolean;
    is_active: boolean;
    is_published: boolean;
    reads_count: number;
    readers: Reader[];
}

interface Props {
    changelog: Changelog;
}

export default function AdminChangelogShow({ changelog }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Admin', href: '/admin' },
        { title: 'Changelogs', href: '/admin/changelogs' },
        { title: changelog.title, href: `/admin/changelogs/${changelog.slug}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${changelog.title} - Changelog`} />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => router.get('/admin/changelogs')}
                        >
                            <ArrowLeft className="h-5 w-5" />
                        </Button>
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-bold">{changelog.title}</h1>
                            {changelog.version && (
                                <Badge variant="secondary">v{changelog.version}</Badge>
                            )}
                            <Badge variant={changelog.is_active ? 'default' : 'secondary'}>
                                {changelog.is_active
                                    ? changelog.is_published ? 'Aktiv' : 'Geplant'
                                    : 'Inaktiv'}
                            </Badge>
                        </div>
                    </div>
                    <Button onClick={() => router.get(`/admin/changelogs/${changelog.slug}/edit`)}>
                        <Edit className="mr-2 h-4 w-4" />
                        Bearbeiten
                    </Button>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium">Datum</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <span className="text-lg font-bold">{changelog.published_at}</span>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium">Gelesen von</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <span className="text-lg font-bold">{changelog.reads_count} Nutzern</span>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium">Modal</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <span className="text-lg font-bold">{changelog.show_modal ? 'Aktiv' : 'Inaktiv'}</span>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Inhalt</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div
                            className="prose max-w-none dark:prose-invert"
                            dangerouslySetInnerHTML={{ __html: changelog.html_content }}
                        />
                    </CardContent>
                </Card>

                {changelog.readers.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Leser ({changelog.reads_count})</CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Gelesen am</TableHead>
                                        <TableHead>Modal geschlossen</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {changelog.readers.map((reader) => (
                                        <TableRow key={reader.id}>
                                            <TableCell className="font-medium">{reader.name}</TableCell>
                                            <TableCell className="text-muted-foreground">{reader.read_at}</TableCell>
                                            <TableCell>
                                                {reader.modal_dismissed ? (
                                                    <Badge variant="secondary">Ja</Badge>
                                                ) : (
                                                    <span className="text-muted-foreground">-</span>
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
