<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types';

const props = defineProps<{
    paginator: Paginated<unknown>;
}>();

const links = computed(() =>
    props.paginator.links.map((link) => ({
        ...link,
        // Laravel sends "&laquo; Previous" / "Next &raquo;" as HTML entities.
        label: link.label
            .replace('&laquo;', '‹')
            .replace('&raquo;', '›')
            .trim(),
    })),
);
</script>

<template>
    <div
        v-if="paginator.last_page > 1"
        class="flex flex-wrap items-center justify-between gap-2"
    >
        <p class="text-sm text-muted-foreground">
            Showing {{ paginator.from }}–{{ paginator.to }} of
            {{ paginator.total }}
        </p>
        <nav class="flex flex-wrap items-center gap-1" aria-label="Pagination">
            <template v-for="link in links" :key="link.label">
                <Button
                    v-if="link.url"
                    as-child
                    size="sm"
                    :variant="link.active ? 'default' : 'outline'"
                >
                    <Link :href="link.url" preserve-scroll>{{
                        link.label
                    }}</Link>
                </Button>
                <Button v-else size="sm" variant="outline" disabled>
                    {{ link.label }}
                </Button>
            </template>
        </nav>
    </div>
</template>
