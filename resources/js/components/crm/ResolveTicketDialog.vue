<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Star } from '@lucide/vue';
import { ref } from 'vue';
import { triage } from '@/actions/App/Http/Controllers/Crm/TicketController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { Ticket } from '@/types';

const props = defineProps<{
    ticket: Ticket;
}>();

const open = ref(false);
const satisfaction = ref<number | null>(props.ticket.satisfaction);

const ratings = [
    { value: 1, label: 'Very dissatisfied' },
    { value: 2, label: 'Dissatisfied' },
    { value: 3, label: 'Neutral' },
    { value: 4, label: 'Satisfied' },
    { value: 5, label: 'Very satisfied' },
];
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <slot />
        </DialogTrigger>
        <DialogContent class="sm:max-w-lg">
            <Form
                v-bind="triage.form(ticket)"
                :options="{ preserveScroll: true }"
                class="space-y-5"
                v-slot="{ errors, processing }"
                @success="open = false"
            >
                <DialogHeader>
                    <DialogTitle>
                        {{
                            ticket.status === 'resolved' ||
                            ticket.status === 'closed'
                                ? 'Update resolution'
                                : `Resolve ${ticket.reference}`
                        }}
                    </DialogTitle>
                    <DialogDescription>
                        Summarise the fix for future reference. Record the
                        customer's satisfaction if they rated the support.
                    </DialogDescription>
                </DialogHeader>

                <input
                    v-if="ticket.status !== 'closed'"
                    type="hidden"
                    name="status"
                    value="resolved"
                />

                <div class="grid gap-2">
                    <Label for="ticket-resolution">Resolution</Label>
                    <Textarea
                        id="ticket-resolution"
                        name="resolution"
                        rows="4"
                        placeholder="What was done to resolve the concern?"
                        :default-value="ticket.resolution ?? ''"
                        required
                        v-focus
                    />
                    <InputError :message="errors.resolution" />
                </div>

                <fieldset class="grid gap-2">
                    <legend class="mb-2 text-sm font-medium">
                        Customer satisfaction
                        <span class="font-normal text-muted-foreground">
                            (optional)
                        </span>
                    </legend>
                    <input
                        type="hidden"
                        name="satisfaction"
                        :value="satisfaction ?? ''"
                    />
                    <div class="flex items-center gap-1">
                        <button
                            v-for="rating in ratings"
                            :key="rating.value"
                            type="button"
                            class="rounded-md p-1 text-muted-foreground/40 transition-colors hover:text-cta focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            :class="{
                                'text-cta':
                                    satisfaction !== null &&
                                    rating.value <= satisfaction,
                            }"
                            :aria-label="`${rating.value} — ${rating.label}`"
                            :aria-pressed="satisfaction === rating.value"
                            @click="
                                satisfaction =
                                    satisfaction === rating.value
                                        ? null
                                        : rating.value
                            "
                        >
                            <Star class="size-6 fill-current" />
                        </button>
                        <span class="ml-2 text-sm text-muted-foreground">
                            {{
                                satisfaction
                                    ? ratings[satisfaction - 1].label
                                    : 'Not rated'
                            }}
                        </span>
                    </div>
                    <InputError :message="errors.satisfaction" />
                </fieldset>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary" type="button"
                            >Cancel</Button
                        >
                    </DialogClose>
                    <Button type="submit" :disabled="processing">
                        {{
                            ticket.status === 'resolved' ||
                            ticket.status === 'closed'
                                ? 'Save'
                                : 'Resolve ticket'
                        }}
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
