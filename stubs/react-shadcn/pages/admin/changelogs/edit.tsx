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
import { MarkdownEditor } from '@peppermint-digital/markdown-editor';
import { ArrowLeft, Save } from 'lucide-react';
import { type FormEventHandler } from 'react';

interface Changelog {
    slug: string;
    version: string;
    title: string;
    content: string;
    published_at: string;
    show_modal: boolean;
    is_active: boolean;
}

interface Props {
    changelog: Changelog;
}

export default function AdminChangelogEdit({ changelog }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        version: changelog.version || '',
        title: changelog.title,
        content: changelog.content,
        published_at: changelog.published_at || '',
        show_modal: changelog.show_modal,
        is_active: changelog.is_active,
    });

    const handleSubmit: FormEventHandler = (e) => {
        e.preventDefault();
        put(`/admin/changelogs/${changelog.slug}`);
    };

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Admin', href: '/admin' },
        { title: 'Changelogs', href: '/admin/changelogs' },
        { title: changelog.title, href: `/admin/changelogs/${changelog.slug}/edit` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${changelog.title} bearbeiten`} />

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
                        <h1 className="text-2xl font-bold">Changelog bearbeiten</h1>
                        <p className="font-mono text-muted-foreground">{changelog.slug}</p>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Grundinformationen</CardTitle>
                            <CardDescription>Titel und Version</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="title">Titel *</Label>
                                    <Input
                                        id="title"
                                        value={data.title}
                                        onChange={(e) => setData('title', e.target.value)}
                                    />
                                    <InputError message={errors.title} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="version">Version</Label>
                                    <Input
                                        id="version"
                                        value={data.version}
                                        onChange={(e) => setData('version', e.target.value)}
                                    />
                                    <InputError message={errors.version} />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Inhalt</CardTitle>
                            <CardDescription>Markdown-Inhalt</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <MarkdownEditor
                                value={data.content}
                                onChange={(value) => setData('content', value)}
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
                            Changelog speichern
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
