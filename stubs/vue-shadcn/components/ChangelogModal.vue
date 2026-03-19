<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { FileText, Sparkles, X } from 'lucide-vue-next';
import { nextTick, onMounted, ref, watch } from 'vue';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';

interface Changelog {
    slug: string;
    version: string | null;
    title: string;
    html_content: string;
}

interface Props {
    changelog: Changelog | null;
}

const props = defineProps<Props>();

const open = ref(false);
const dismissing = ref(false);
const mounted = ref(false);

// Wait for component to be fully mounted before allowing modal to open
// This prevents hydration issues where the modal opens before Vue is ready
onMounted(() => {
    nextTick(() => {
        mounted.value = true;
        if (props.changelog) {
            open.value = true;
        }
    });
});

watch(() => props.changelog, (newVal) => {
    if (newVal && mounted.value) {
        open.value = true;
    }
});

function dismissModal() {
    if (!props.changelog) return;

    dismissing.value = true;
    router.post(`/changelogs/${props.changelog.slug}/dismiss-modal`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            dismissing.value = false;
        },
        onError: () => {
            dismissing.value = false;
        },
    });
}

function readMore() {
    if (!props.changelog) return;

    dismissing.value = true;
    router.visit(`/changelogs/${props.changelog.slug}`, {
        onSuccess: () => {
            open.value = false;
        },
    });
}

function handleOpenChange(newOpen: boolean) {
    if (!newOpen && !dismissing.value) {
        dismissModal();
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="handleOpenChange">
        <DialogContent class="sm:max-w-lg" @escape-key-down.prevent @pointer-down-outside.prevent>
            <DialogHeader>
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10">
                        <Sparkles class="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <Badge v-if="changelog?.version" variant="outline">v{{ changelog.version }}</Badge>
                            <DialogTitle>{{ changelog?.title }}</DialogTitle>
                        </div>
                        <DialogDescription>Neues Update verfügbar</DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <div class="max-h-80 overflow-y-auto">
                <div class="prose prose-sm dark:prose-invert max-w-none" v-html="changelog?.html_content" />
            </div>

            <DialogFooter class="flex gap-2 sm:justify-between">
                <Button
                    variant="ghost"
                    size="sm"
                    :disabled="dismissing"
                    @click="dismissModal"
                >
                    <X class="mr-2 h-4 w-4" />
                    Schließen
                </Button>
                <Button :disabled="dismissing" @click="readMore">
                    <FileText class="mr-2 h-4 w-4" />
                    Vollständiger Changelog
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
