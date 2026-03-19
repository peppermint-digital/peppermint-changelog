import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { MarkdownEditor } from '@peppermint-digital/react-markdown-editor';
import { ArrowLeft, Save } from 'lucide-react';
import { type FormEventHandler } from 'react';

export default function AdminChangelogCreate() {
    const { data, setData, post, processing, errors } = useForm({
        slug: '',
        version: '',
        title: '',
        content: '',
        published_at: '',
        show_modal: true,
        is_active: true,
    });

    const handleSubmit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/admin/changelogs');
    };

    const handleTitleChange = (title: string) => {
        setData('title', title);
        if (!data.slug) {
            setData('slug', title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''));
        }
    };

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Admin', href: '/admin' },
        { title: 'Changelogs', href: '/admin/changelogs' },
        { title: 'Erstellen', href: '/admin/changelogs/create' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Changelog erstellen" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => router.get('/admin/changelogs')}
                    >
                        <ArrowLeft className="h-5 w-5" />
                    </Button>
                    <div>
                        <h1 className="text-2xl font-bold">Changelog erstellen</h1>
                        <p className="text-muted-foreground">Neues Update dokumentieren</p>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Grundinformationen</CardTitle>
                            <CardDescription>Titel, Version und Slug</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="title">Titel *</Label>
                                    <Input
                                        id="title"
                                        value={data.title}
                                        onChange={(e) => handleTitleChange(e.target.value)}
                                        placeholder="z.B. Neue Gutscheinfunktion"
                                    />
                                    <InputError message={errors.title} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="version">Version</Label>
                                    <Input
                                        id="version"
                                        value={data.version}
                                        onChange={(e) => setData('version', e.target.value)}
                                        placeholder="z.B. 1.2.0"
                                    />
                                    <InputError message={errors.version} />
                                </div>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="slug">Slug *</Label>
                                <Input
                                    id="slug"
                                    value={data.slug}
                                    onChange={(e) => setData('slug', e.target.value)}
                                    placeholder="z.B. v1-2-0"
                                    className="font-mono"
                                />
                                <p className="text-sm text-muted-foreground">
                                    Nur Kleinbuchstaben, Zahlen und Bindestriche
                                </p>
                                <InputError message={errors.slug} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Inhalt</CardTitle>
                            <CardDescription>Markdown-Inhalt des Changelogs</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <MarkdownEditor
                                value={data.content}
                                onChange={(value) => setData('content', value)}
                                placeholder="## Neue Features&#10;&#10;- Feature 1&#10;- Feature 2"
                                minHeight="300px"
                            />
                            <InputError message={errors.content} />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Einstellungen</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="published_at">Veroeffentlichungsdatum</Label>
                                <Input
                                    id="published_at"
                                    type="datetime-local"
                                    value={data.published_at}
                                    onChange={(e) => setData('published_at', e.target.value)}
                                />
                                <p className="text-sm text-muted-foreground">
                                    Leer lassen fuer sofortige Veroeffentlichung
                                </p>
                                <InputError message={errors.published_at} />
                            </div>

                            <div className="flex items-center gap-3">
                                <Checkbox
                                    id="show_modal"
                                    checked={data.show_modal}
                                    onCheckedChange={(checked) => setData('show_modal', checked === true)}
                                />
                                <Label htmlFor="show_modal" className="cursor-pointer">
                                    Als Modal-Benachrichtigung anzeigen
                                </Label>
                            </div>

                            <div className="flex items-center gap-3">
                                <Checkbox
                                    id="is_active"
                                    checked={data.is_active}
                                    onCheckedChange={(checked) => setData('is_active', checked === true)}
                                />
                                <Label htmlFor="is_active" className="cursor-pointer">
                                    Changelog ist aktiv
                                </Label>
                            </div>
                        </CardContent>
                    </Card>

                    <Separator />

                    <div className="flex justify-end gap-4">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => router.get('/admin/changelogs')}
                        >
                            Abbrechen
                        </Button>
                        <Button type="submit" disabled={processing}>
                            <Save className="mr-2 h-4 w-4" />
                            Changelog erstellen
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
