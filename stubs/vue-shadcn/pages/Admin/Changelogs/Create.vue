<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import { computed } from 'vue';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Administration', href: '/admin/users' },
    { title: 'Changelogs', href: '/admin/changelogs' },
    { title: 'Erstellen', href: '/admin/changelogs/create' },
];

const form = useForm({
    slug: '',
    version: '',
    title: '',
    content: '',
    published_at: '',
    show_modal: true,
    is_active: true,
});

const generatedSlug = computed(() => {
    if (form.slug) return form.slug;
    const source = form.version || form.title;
    return source.toLowerCase()
        .replace(/[^a-z0-9.]+/g, '-')
        .replace(/^-|-$/g, '');
});

function submit() {
    form.transform((data) => ({
        ...data,
        slug: generatedSlug.value,
    })).post('/admin/changelogs');
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Changelog erstellen" />

        <div class="space-y-6 p-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="icon" as-child>
                    <Link href="/admin/changelogs">
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                </Button>
                <div>
                    <h1 class="text-3xl font-bold">Neuer Changelog</h1>
                    <p class="text-muted-foreground">Erstellen Sie einen neuen Changelog-Eintrag</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid gap-6 lg:grid-cols-3">
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Grunddaten</CardTitle>
                                <CardDescription>Titel, Version und Inhalt des Changelogs</CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="space-y-2">
                                        <Label for="version">Version</Label>
                                        <Input
                                            id="version"
                                            v-model="form.version"
                                            placeholder="z.B. 1.2.0"
                                        />
                                        <p v-if="form.errors.version" class="text-sm text-destructive">{{ form.errors.version }}</p>
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="slug">Slug (Dateiname)</Label>
                                        <Input
                                            id="slug"
                                            v-model="form.slug"
                                            :placeholder="generatedSlug || 'wird automatisch generiert'"
                                        />
                                        <p class="text-xs text-muted-foreground">Wird als Dateiname verwendet: {{ generatedSlug || '...' }}.md</p>
                                        <p v-if="form.errors.slug" class="text-sm text-destructive">{{ form.errors.slug }}</p>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <Label for="title">Titel *</Label>
                                    <Input
                                        id="title"
                                        v-model="form.title"
                                        placeholder="Changelog-Titel"
                                        required
                                    />
                                    <p v-if="form.errors.title" class="text-sm text-destructive">{{ form.errors.title }}</p>
                                </div>

                                <div class="space-y-2">
                                    <Label for="content">Inhalt (Markdown) *</Label>
                                    <Textarea
                                        id="content"
                                        v-model="form.content"
                                        placeholder="## Neue Features&#10;&#10;- Feature 1&#10;- Feature 2&#10;&#10;## Fehlerbehebungen&#10;&#10;- Bug 1 behoben"
                                        rows="15"
                                        class="font-mono text-sm"
                                        required
                                    />
                                    <p class="text-xs text-muted-foreground">Verwenden Sie Markdown-Formatierung</p>
                                    <p v-if="form.errors.content" class="text-sm text-destructive">{{ form.errors.content }}</p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Veröffentlichung</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="space-y-2">
                                    <Label for="published_at">Veröffentlichungsdatum</Label>
                                    <Input
                                        id="published_at"
                                        v-model="form.published_at"
                                        type="datetime-local"
                                    />
                                    <p class="text-xs text-muted-foreground">Leer lassen für sofortige Veröffentlichung</p>
                                </div>

                                <div class="flex items-center space-x-2">
                                    <Checkbox id="is_active" v-model="form.is_active" />
                                    <Label for="is_active" class="text-sm font-normal">Aktiv</Label>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Anzeige</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="flex items-center space-x-2">
                                    <Checkbox id="show_modal" v-model="form.show_modal" />
                                    <Label for="show_modal" class="text-sm font-normal">Als Modal anzeigen</Label>
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    Zeigt diesen Changelog als Modal an, bis der Benutzer ihn schließt
                                </p>
                            </CardContent>
                        </Card>

                        <div class="flex gap-2">
                            <Button type="submit" :disabled="form.processing" class="flex-1">
                                <Save class="mr-2 h-4 w-4" />
                                Speichern
                            </Button>
                            <Button type="button" variant="outline" as-child>
                                <Link href="/admin/changelogs">Abbrechen</Link>
                            </Button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
