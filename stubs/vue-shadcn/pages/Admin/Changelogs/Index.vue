<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Calendar, CheckCircle, Edit, Eye, FileText, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Changelog {
    slug: string;
    version: string | null;
    title: string;
    is_markdown: boolean;
    published_at: string | null;
    show_modal: boolean;
    is_active: boolean;
    is_published: boolean;
    reads_count: number;
}

interface Props {
    changelogs: Changelog[];
}

defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Administration', href: '/admin/users' },
    { title: 'Changelogs', href: '/admin/changelogs' },
];

const showDeleteDialog = ref(false);
const deletingChangelog = ref<Changelog | null>(null);

function openDeleteDialog(changelog: Changelog, event: Event) {
    event.stopPropagation();
    deletingChangelog.value = changelog;
    showDeleteDialog.value = true;
}

function confirmDelete() {
    if (!deletingChangelog.value) return;

    router.delete(`/admin/changelogs/${deletingChangelog.value.slug}`, {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteDialog.value = false;
            deletingChangelog.value = null;
        },
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Changelogs verwalten" />

        <div class="space-y-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Changelogs</h1>
                    <p class="text-muted-foreground">Dateibasierte Changelogs im <code>changelogs/</code> Ordner</p>
                </div>
                <Button as-child>
                    <Link href="/admin/changelogs/create">
                        <Plus class="mr-2 h-4 w-4" />
                        Neuer Changelog
                    </Link>
                </Button>
            </div>

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Gesamt</CardTitle>
                        <FileText class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ changelogs.length }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Aktiv</CardTitle>
                        <CheckCircle class="h-4 w-4 text-green-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ changelogs.filter(c => c.is_active && c.is_published).length }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Mit Modal</CardTitle>
                        <Calendar class="h-4 w-4 text-blue-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ changelogs.filter(c => c.show_modal).length }}</div>
                    </CardContent>
                </Card>
            </div>

            <!-- Changelogs Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Alle Changelogs</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table v-if="changelogs.length > 0">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Version / Titel</TableHead>
                                <TableHead>Format</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Veröffentlicht</TableHead>
                                <TableHead>Gelesen</TableHead>
                                <TableHead class="w-[100px]">Aktionen</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="changelog in changelogs" :key="changelog.slug">
                                <TableCell>
                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-2">
                                            <Badge v-if="changelog.version" variant="outline">v{{ changelog.version }}</Badge>
                                            <span class="font-medium">{{ changelog.title }}</span>
                                        </div>
                                        <span class="text-xs text-muted-foreground">{{ changelog.slug }}.md</span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="secondary">Markdown</Badge>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <Badge v-if="!changelog.is_active" variant="secondary">Inaktiv</Badge>
                                        <Badge v-else-if="!changelog.is_published" variant="outline">Geplant</Badge>
                                        <Badge v-else variant="default" class="bg-green-500">Aktiv</Badge>
                                        <Badge v-if="changelog.show_modal" variant="outline" class="text-xs">Modal</Badge>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <span v-if="changelog.published_at">{{ changelog.published_at }}</span>
                                    <span v-else class="text-muted-foreground">Sofort</span>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="outline">{{ changelog.reads_count }}</Badge>
                                </TableCell>
                                <TableCell>
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button variant="ghost" size="icon" class="h-8 w-8" @click.stop>
                                                <span class="sr-only">Menü öffnen</span>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/></svg>
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem as-child>
                                                <Link :href="`/admin/changelogs/${changelog.slug}`">
                                                    <Eye class="mr-2 h-4 w-4" />
                                                    Anzeigen
                                                </Link>
                                            </DropdownMenuItem>
                                            <DropdownMenuItem as-child>
                                                <Link :href="`/admin/changelogs/${changelog.slug}/edit`">
                                                    <Edit class="mr-2 h-4 w-4" />
                                                    Bearbeiten
                                                </Link>
                                            </DropdownMenuItem>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem class="text-destructive" @click="openDeleteDialog(changelog, $event)">
                                                <Trash2 class="mr-2 h-4 w-4" />
                                                Löschen
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <!-- Empty State -->
                    <div v-else class="py-12 text-center">
                        <FileText class="mx-auto h-12 w-12 text-muted-foreground/50" />
                        <h3 class="mt-2 text-sm font-semibold">Keine Changelogs</h3>
                        <p class="mt-1 text-sm text-muted-foreground">Erstellen Sie einen neuen Changelog.</p>
                        <div class="mt-6">
                            <Button as-child>
                                <Link href="/admin/changelogs/create">
                                    <Plus class="mr-2 h-4 w-4" />
                                    Ersten Changelog erstellen
                                </Link>
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog :open="showDeleteDialog" @update:open="showDeleteDialog = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Changelog löschen?</DialogTitle>
                    <DialogDescription>
                        Möchten Sie den Changelog "{{ deletingChangelog?.title }}" wirklich löschen?
                        Die Datei {{ deletingChangelog?.slug }}.md wird unwiderruflich gelöscht.
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
