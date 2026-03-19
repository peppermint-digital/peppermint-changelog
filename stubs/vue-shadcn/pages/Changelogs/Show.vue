<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, FileText, Tag } from 'lucide-vue-next';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Changelog {
    slug: string;
    version: string | null;
    title: string;
    content: string;
    html_content: string;
    is_markdown: boolean;
    published_at: string | null;
}

interface Props {
    changelog: Changelog;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Changelogs', href: '/changelogs' },
    { title: props.changelog.title, href: `/changelogs/${props.changelog.slug}` },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="changelog.title" />

        <div class="space-y-6 p-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="icon" as-child>
                    <Link href="/changelogs">
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                </Button>
                <div>
                    <div class="flex items-center gap-2">
                        <Badge v-if="changelog.version" variant="outline" class="text-sm">v{{ changelog.version }}</Badge>
                        <h1 class="text-3xl font-bold">{{ changelog.title }}</h1>
                    </div>
                    <p class="text-muted-foreground">Changelog</p>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Main Content -->
                <div class="lg:col-span-2">
                    <Card>
                        <CardHeader>
                            <div class="flex items-center gap-3">
                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10">
                                    <FileText class="h-6 w-6 text-primary" />
                                </div>
                                <div>
                                    <CardTitle class="flex items-center gap-2">
                                        <Badge v-if="changelog.version" variant="outline">v{{ changelog.version }}</Badge>
                                        {{ changelog.title }}
                                    </CardTitle>
                                    <CardDescription v-if="changelog.published_at">
                                        {{ changelog.published_at }}
                                    </CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="prose prose-sm dark:prose-invert max-w-none" v-html="changelog.html_content" />
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Details</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div v-if="changelog.version" class="flex items-center gap-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-muted">
                                    <Tag class="h-4 w-4 text-muted-foreground" />
                                </div>
                                <div>
                                    <div class="text-sm font-medium">v{{ changelog.version }}</div>
                                    <div class="text-xs text-muted-foreground">Version</div>
                                </div>
                            </div>
                            <div v-if="changelog.published_at" class="flex items-center gap-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-muted">
                                    <Calendar class="h-4 w-4 text-muted-foreground" />
                                </div>
                                <div>
                                    <div class="text-sm font-medium">{{ changelog.published_at }}</div>
                                    <div class="text-xs text-muted-foreground">Veröffentlicht</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-muted">
                                    <FileText class="h-4 w-4 text-muted-foreground" />
                                </div>
                                <div>
                                    <div class="text-sm font-medium">{{ changelog.is_markdown ? 'Markdown' : 'HTML' }}</div>
                                    <div class="text-xs text-muted-foreground">Format</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Button variant="outline" class="w-full" as-child>
                        <Link href="/changelogs">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Alle Changelogs
                        </Link>
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
