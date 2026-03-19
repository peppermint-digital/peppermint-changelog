import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
import { BookOpen, Edit, Eye, Plus, Trash2 } from 'lucide-react';

interface Changelog {
    slug: string;
    version: string;
    title: string;
    published_at: string;
    show_modal: boolean;
    is_active: boolean;
    is_published: boolean;
    reads_count: number;
}

interface Props {
    changelogs: Changelog[];
}

export default function AdminChangelogIndex({ changelogs }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Admin', href: '/admin' },
        { title: 'Changelogs', href: '/admin/changelogs' },
    ];

    const handleDelete = (changelog: Changelog) => {
        if (confirm(`Changelog "${changelog.title}" wirklich loeschen?`)) {
            router.delete(`/admin/changelogs/${changelog.slug}`);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Changelogs verwalten" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">Changelogs</h1>
                        <p className="text-muted-foreground">
                            Updates und Release-Notes verwalten
                        </p>
                    </div>
                    <Button onClick={() => router.get('/admin/changelogs/create')}>
                        <Plus className="mr-2 h-4 w-4" />
                        Changelog erstellen
                    </Button>
                </div>

                {changelogs.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <BookOpen className="mb-4 h-12 w-12 text-muted-foreground" />
                            <h3 className="mb-2 text-lg font-semibold">Noch keine Changelogs</h3>
                            <p className="mb-4 text-muted-foreground">
                                Erstelle den ersten Changelog-Eintrag.
                            </p>
                            <Button onClick={() => router.get('/admin/changelogs/create')}>
                                <Plus className="mr-2 h-4 w-4" />
                                Changelog erstellen
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Version</TableHead>
                                        <TableHead>Titel</TableHead>
                                        <TableHead>Datum</TableHead>
                                        <TableHead>Modal</TableHead>
                                        <TableHead className="text-right">Gelesen</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">Aktionen</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {changelogs.map((changelog) => (
                                        <TableRow key={changelog.slug}>
                                            <TableCell className="font-mono font-medium">
                                                {changelog.version || '-'}
                                            </TableCell>
                                            <TableCell>{changelog.title}</TableCell>
                                            <TableCell className="text-sm text-muted-foreground">
                                                {changelog.published_at}
                                            </TableCell>
                                            <TableCell>
                                                {changelog.show_modal ? (
                                                    <Badge variant="secondary">Ja</Badge>
                                                ) : (
                                                    <span className="text-muted-foreground">-</span>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {changelog.reads_count}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant={changelog.is_active ? 'default' : 'secondary'}>
                                                    {changelog.is_active
                                                        ? changelog.is_published ? 'Aktiv' : 'Geplant'
                                                        : 'Inaktiv'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex items-center justify-end gap-2">
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() => router.get(`/admin/changelogs/${changelog.slug}`)}
                                                    >
                                                        <Eye className="h-4 w-4" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() => router.get(`/admin/changelogs/${changelog.slug}/edit`)}
                                                    >
                                                        <Edit className="h-4 w-4" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() => handleDelete(changelog)}
                                                    >
                                                        <Trash2 className="h-4 w-4 text-destructive" />
                                                    </Button>
                                                </div>
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
