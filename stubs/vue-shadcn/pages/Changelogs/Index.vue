<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { CheckCircle, FileText, Sparkles } from 'lucide-vue-next';

import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Changelog {
    slug: string;
    version: string | null;
    title: string;
    html_content: string;
    published_at: string | null;
    is_read: boolean;
}

interface Props {
    changelogs: Changelog[];
}

defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Changelogs', href: '/changelogs' },
];

function stripHtml(html: string): string {
    const doc = new DOMParser().parseFromString(html, 'text/html');
    return doc.body.textContent || '';
}

function truncateText(text: string, maxLength: number = 200): string {
    const stripped = stripHtml(text);
    if (stripped.length <= maxLength) return stripped;
    return stripped.substring(0, maxLength).trim() + '...';
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Changelogs" />

        <div class="space-y-6 p-6">
            <!-- Header -->
            <div>
                <h1 class="text-3xl font-bold">Changelogs</h1>
                <p class="text-muted-foreground">Versionshistorie und Updates</p>
            </div>

            <!-- Changelogs List -->
            <div v-if="changelogs.length > 0" class="space-y-4">
                <Card
                    v-for="changelog in changelogs"
                    :key="changelog.slug"
                    class="cursor-pointer transition-all hover:shadow-md"
                    :class="{ 'border-primary/50 bg-primary/5': !changelog.is_read }"
                    @click="router.visit(`/changelogs/${changelog.slug}`)"
                >
                    <CardHeader>
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start gap-3">
                                <div :class="[
                                    'flex h-10 w-10 shrink-0 items-center justify-center rounded-full',
                                    !changelog.is_read ? 'bg-primary/10' : 'bg-muted'
                                ]">
                                    <Sparkles v-if="!changelog.is_read" class="h-5 w-5 text-primary" />
                                    <CheckCircle v-else class="h-5 w-5 text-muted-foreground" />
                                </div>
                                <div>
                                    <CardTitle class="flex items-center gap-2 text-lg">
                                        <Badge v-if="changelog.version" variant="outline">v{{ changelog.version }}</Badge>
                                        {{ changelog.title }}
                                        <Badge v-if="!changelog.is_read" variant="default" class="text-xs">Neu</Badge>
                                    </CardTitle>
                                    <CardDescription v-if="changelog.published_at" class="mt-1">
                                        {{ changelog.published_at }}
                                    </CardDescription>
                                </div>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <p class="text-sm text-muted-foreground">
                            {{ truncateText(changelog.html_content) }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <div v-else class="flex flex-col items-center justify-center py-16 text-center">
                <FileText class="mb-4 h-16 w-16 text-muted-foreground/50" />
                <h3 class="text-lg font-semibold">Keine Changelogs</h3>
                <p class="text-sm text-muted-foreground">Es gibt derzeit keine Changelogs.</p>
            </div>
        </div>
    </AppLayout>
</template>
