import type { RouteDefinition } from '@/wayfinder';
import { router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { reactive, watch } from 'vue';

/**
 * Keep list filters in the query string, reloading the page as they change.
 */
export function useFilters<T extends Record<string, string>>(
    initial: T,
    route: () => RouteDefinition<'get'>,
) {
    const filters = reactive({ ...initial }) as T;

    const apply = useDebounceFn(() => {
        const query = Object.fromEntries(
            Object.entries(filters).filter(([, value]) => value !== ''),
        );

        router.get(route().url, query, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }, 300);

    watch(filters, () => void apply());

    return filters;
}
