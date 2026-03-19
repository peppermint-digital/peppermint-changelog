<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Calendar,
    Edit,
    Eye,
    FileText,
    MessageSquare,
    Trash2,
} from 'lucide-vue-next';
import { ref } from 'vue';

import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { ScrollArea } from '@/components/ui/scroll-area';
import { useInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Reader {
    id: number;
    name: string;
    read_at: string;
    modal_dismissed: boolean;
}

interface Changelog {
    slug: string;
    version: string | null;
    title: string;
    content: string;
    html_content: string;
    is_markdown: boolean;
    published_at: string | null;
    show_modal: boolean;
    is_active: boolean;
    is_published: boolean;
    reads_count: number;
    readers: Reader[];
}

interface Props {
    changelog: Changelog;
}

const props = defineProps<Props>();

const { getInitials } = useInitials();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Administration', href: '/admin/users' },
    { title: 'Changelogs', href: '/admin/changelogs' },
    { title: props.changelog.title, href: `/admin/changelogs/${props.changelog.slug}` },
];

const showDeleteDialog = ref(false);

function confirmDelete() {
    router.delete(`/admin/changelogs/${props.changelog.slug}`, {
        onSuccess: () => {
            showDeleteDialog.value = false;
        },
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="changelog.title" />

        <div class="space-y-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="ghost" size="icon" as-child>
                        <Link href="/admin/changelogs">
                            <ArrowLeft class="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <div class="flex items-center gap-2">
                            <Badge v-if="changelog.version" variant="outline">v{{ changelog.version }}</Badge>
                            <h1 class="text-3xl font-bold">{{ changelog.title }}</h1>
                        </div>
                        <p class="text-muted-foreground">{{ changelog.slug }}.md</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Button variant="outline" as-child>
                        <Link :href="`/admin/changelogs/${changelog.slug}/edit`">
                            <Edit class="mr-2 h-4 w-4" />
                            Bearbeiten
                        </Link>
                    </Button>
                    <Button
                        variant="outline"
                        class="text-destructive hover:bg-destructive/10"
                        @click="showDeleteDialog = true"
                    >
                        <Trash2 class="mr-2 h-4 w-4" />
                        Löschen
                    </Button>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Content Card -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <FileText class="h-5 w-5" />
                                Inhalt
                            </CardTitle>
                            <CardDescription>
                                Markdown-Formatierung
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="prose prose-sm dark:prose-invert max-w-none" v-html="changelog.html_content" />
                        </CardContent>
                    </Card>

                    <!-- Raw Content (Markdown) -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Quelltext (Markdown)</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <pre class="rounded-lg bg-muted p-4 text-sm overflow-auto max-h-96 font-mono">{{ changelog.content }}</pre>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Status Card -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Status</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">Status</span>
                                <Badge v-if="!changelog.is_active" variant="secondary">Inaktiv</Badge>
                                <Badge v-else-if="!changelog.is_published" variant="outline">Geplant</Badge>
                                <Badge v-else variant="default" class="bg-green-500">Aktiv</Badge>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">Format</span>
                                <Badge variant="secondary">Markdown</Badge>
                            </div>
                            <Collapsible>
                                <CollapsibleTrigger class="flex w-full items-center justify-between">
                                    <span class="text-sm text-muted-foreground">Gelesen von</span>
                                    <Badge variant="outline" class="cursor-pointer hover:bg-muted">
                                        <Eye class="mr-1 h-3 w-3" />
                                        {{ changelog.reads_count }} Benutzer
                                    </Badge>
                                </CollapsibleTrigger>
                                <CollapsibleContent>
                                    <div v-if="changelog.readers.length === 0" class="mt-3 text-sm text-muted-foreground">
                                        Noch niemand hat diesen Changelog gelesen.
                                    </div>
                                    <ScrollArea v-else class="mt-3 max-h-48">
                                        <div class="space-y-2">
                                            <div
                                                v-for="reader in changelog.readers"
                                                :key="reader.id"
                                                class="flex items-center gap-3 rounded-md border p-2"
                                            >
                                                <Avatar class="h-8 w-8">
                                                    <AvatarFallback class="text-xs">{{ getInitials(reader.name) }}</AvatarFallback>
                                                </Avatar>
                                                <div class="flex-1 min-w-0">
                                                    <div class="text-sm font-medium truncate">{{ reader.name }}</div>
                                                    <div class="text-xs text-muted-foreground">{{ reader.read_at }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </ScrollArea>
                                </CollapsibleContent>
                            </Collapsible>
                        </CardContent>
                    </Card>

                    <!-- Display Options -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Anzeigeoptionen</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div class="flex items-center gap-3">
                                <div :class="[
                                    'flex h-8 w-8 items-center justify-center rounded-full',
                                    changelog.show_modal ? 'bg-green-100 dark:bg-green-900' : 'bg-gray-100 dark:bg-gray-800'
                                ]">
                                    <MessageSquare :class="[
                                        'h-4 w-4',
                                        changelog.show_modal ? 'text-green-600 dark:text-green-400' : 'text-gray-400'
                                    ]" />
                                </div>
                                <div>
                                    <div class="text-sm font-medium">Modal anzeigen</div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ changelog.show_modal ? 'Aktiviert' : 'Deaktiviert' }}
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Scheduling -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Zeitplanung</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900">
                                    <Calendar class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                </div>
                                <div>
                                    <div class="text-sm font-medium">Veröffentlichung</div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ changelog.published_at || 'Sofort' }}
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Meta Info -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Informationen</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3 text-sm">
                            <div v-if="changelog.version">
                                <div class="text-muted-foreground">Version</div>
                                <div class="font-medium">v{{ changelog.version }}</div>
                            </div>
                            <div>
                                <div class="text-muted-foreground">Datei</div>
                                <div class="font-medium font-mono text-xs">changelogs/{{ changelog.slug }}.md</div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog :open="showDeleteDialog" @update:open="showDeleteDialog = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Changelog löschen?</DialogTitle>
                    <DialogDescription>
                        Möchten Sie den Changelog "{{ changelog.title }}" wirklich löschen?
                        Die Datei {{ changelog.slug }}.md wird unwiderruflich gelöscht.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="showDeleteDialog = false">Abbrechen</Button>
                    <Button type="button" variant="destructive" @click="confirmDelete">Löschen</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
