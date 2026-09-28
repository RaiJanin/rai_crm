<script setup lang="ts">
import { Form, usePage } from '@inertiajs/vue3';
import { FileText, Paperclip, Trash2 } from '@lucide/vue';
import {
    destroy,
    download,
    store,
} from '@/actions/App/Http/Controllers/Crm/AttachmentController';
import ConfirmAction from '@/components/crm/ConfirmAction.vue';
import EmptyState from '@/components/crm/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { useFormatters } from '@/composables/useFormatters';
import type { Attachment } from '@/types';

defineProps<{
    type: 'company' | 'contact' | 'deal' | 'task' | 'ticket';
    id: number;
    attachments: Attachment[];
}>();

const page = usePage();
const { relative } = useFormatters();

function fileSize(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
}

function canDelete(attachment: Attachment): boolean {
    return (
        attachment.uploaded_by === page.props.auth.user.id ||
        page.props.auth.isAdmin
    );
}
</script>

<template>
    <div class="space-y-6">
        <Form
            v-bind="store.form()"
            :options="{ preserveScroll: true }"
            reset-on-success
            class="space-y-2"
            v-slot="{ errors, processing, progress }"
        >
            <input type="hidden" name="attachable_type" :value="type" />
            <input type="hidden" name="attachable_id" :value="id" />
            <div class="flex flex-wrap items-center gap-2">
                <input
                    type="file"
                    name="file"
                    aria-label="File"
                    required
                    class="h-9 w-full max-w-sm min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs file:mr-3 file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground dark:bg-input/30"
                />
                <Button type="submit" size="sm" :disabled="processing">
                    <Paperclip />
                    Upload
                </Button>
                <span v-if="progress" class="text-xs text-muted-foreground">
                    {{ progress.percentage }}%
                </span>
            </div>
            <InputError :message="errors.file" />
        </Form>

        <EmptyState
            v-if="attachments.length === 0"
            :icon="Paperclip"
            title="No files"
            description="Proposals, contracts and other documents live here."
        />

        <ul v-else class="divide-y rounded-lg border">
            <li
                v-for="attachment in attachments"
                :key="attachment.attachment_id"
                class="flex items-center gap-3 px-4 py-3"
            >
                <FileText class="size-4 shrink-0 text-muted-foreground" />
                <div class="min-w-0 flex-1">
                    <a
                        :href="download.url(attachment.hash_id)"
                        class="block truncate text-sm font-medium hover:underline"
                    >
                        {{ attachment.file_name }}
                    </a>
                    <p class="text-xs text-muted-foreground">
                        {{ fileSize(attachment.file_size) }} ·
                        {{ attachment.uploader?.name ?? 'Unknown' }} ·
                        {{ relative(attachment.created_at) }}
                    </p>
                </div>
                <ConfirmAction
                    v-if="canDelete(attachment)"
                    :action="destroy(attachment.hash_id)"
                    title="Remove this file?"
                    description="The file will be permanently deleted."
                    confirm-label="Remove"
                >
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        aria-label="Remove file"
                    >
                        <Trash2 />
                    </Button>
                </ConfirmAction>
            </li>
        </ul>
    </div>
</template>
