<script setup lang="ts">
import { Form, usePage } from '@inertiajs/vue3';
import { Lock, MessageSquare, Send, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import {
    destroy,
    store,
} from '@/actions/App/Http/Controllers/Crm/CommentController';
import ConfirmAction from '@/components/crm/ConfirmAction.vue';
import EmptyState from '@/components/crm/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { useFormatters } from '@/composables/useFormatters';
import { getInitials } from '@/composables/useInitials';
import { cn } from '@/lib/utils';
import type { Comment } from '@/types';

const props = withDefaults(
    defineProps<{
        type: 'company' | 'contact' | 'deal' | 'ticket';
        id: number;
        comments: Comment[];
        /** Tickets: log customer replies alongside internal notes. */
        allowReplies?: boolean;
    }>(),
    {
        allowReplies: false,
    },
);

const page = usePage();
const { relative } = useFormatters();

const mode = ref<'reply' | 'note'>(props.allowReplies ? 'reply' : 'note');

function canDelete(comment: Comment): boolean {
    return (
        comment.user_id === page.props.auth.user.id || page.props.auth.isAdmin
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
            v-slot="{ errors, processing }"
        >
            <input type="hidden" name="commentable_type" :value="type" />
            <input type="hidden" name="commentable_id" :value="id" />
            <input
                type="hidden"
                name="is_public"
                :value="mode === 'reply' ? 1 : 0"
            />

            <div
                v-if="allowReplies"
                class="inline-flex gap-1 rounded-lg bg-muted p-1"
                role="radiogroup"
                aria-label="Entry type"
            >
                <button
                    v-for="option in [
                        {
                            value: 'reply',
                            label: 'Reply to customer',
                            icon: Send,
                        },
                        { value: 'note', label: 'Internal note', icon: Lock },
                    ] as const"
                    :key="option.value"
                    type="button"
                    role="radio"
                    :aria-checked="mode === option.value"
                    :class="
                        cn(
                            'inline-flex items-center gap-1.5 rounded-md px-3 py-1 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground',
                            mode === option.value &&
                                'bg-card text-foreground shadow-xs',
                        )
                    "
                    @click="mode = option.value"
                >
                    <component :is="option.icon" class="size-3.5" />
                    {{ option.label }}
                </button>
            </div>

            <Textarea
                name="body"
                rows="3"
                :placeholder="
                    mode === 'reply'
                        ? 'Log what you told the customer (email, call, chat)…'
                        : 'Add an internal note — not shared with the customer…'
                "
                :aria-label="mode === 'reply' ? 'Reply' : 'Note'"
                :class="{
                    'border-highlight-foreground/30 bg-highlight/40':
                        allowReplies && mode === 'note',
                }"
                required
            />
            <InputError :message="errors.body" />
            <div class="flex justify-end">
                <Button type="submit" size="sm" :disabled="processing">
                    {{ mode === 'reply' ? 'Log reply' : 'Add note' }}
                </Button>
            </div>
        </Form>

        <EmptyState
            v-if="comments.length === 0"
            :icon="MessageSquare"
            :title="allowReplies ? 'No conversation yet' : 'No notes yet'"
            :description="
                allowReplies
                    ? 'The first reply logged to the customer stops the first-response SLA clock.'
                    : 'Notes and call logs you add will show up here.'
            "
        />

        <ul v-else class="space-y-4">
            <li
                v-for="comment in comments"
                :key="comment.comment_id"
                :class="
                    cn(
                        'flex gap-3',
                        allowReplies &&
                            'rounded-lg border p-3 ' +
                                (comment.is_public
                                    ? 'border-border bg-card'
                                    : 'border-highlight-foreground/20 bg-highlight/40'),
                    )
                "
            >
                <Avatar class="size-8">
                    <AvatarFallback class="text-xs">
                        {{ getInitials(comment.user?.name ?? '?') }}
                    </AvatarFallback>
                </Avatar>
                <div class="min-w-0 flex-1 space-y-1">
                    <div class="flex items-center gap-2 text-sm">
                        <span class="font-medium">
                            {{ comment.user?.name ?? 'Deleted user' }}
                        </span>
                        <Badge
                            v-if="allowReplies"
                            class="border-transparent"
                            :class="
                                comment.is_public
                                    ? 'bg-secondary text-secondary-foreground'
                                    : 'bg-highlight text-highlight-foreground'
                            "
                        >
                            <Send v-if="comment.is_public" />
                            <Lock v-else />
                            {{ comment.is_public ? 'Reply' : 'Internal' }}
                        </Badge>
                        <span class="text-xs text-muted-foreground">
                            {{ relative(comment.created_at) }}
                        </span>
                        <ConfirmAction
                            v-if="canDelete(comment)"
                            :action="destroy(comment.hash_id)"
                            title="Delete this entry?"
                        >
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                class="ml-auto size-7"
                                aria-label="Delete entry"
                            >
                                <Trash2 class="size-3.5" />
                            </Button>
                        </ConfirmAction>
                    </div>
                    <p class="text-sm whitespace-pre-line text-foreground/90">
                        {{ comment.body }}
                    </p>
                </div>
            </li>
        </ul>
    </div>
</template>
