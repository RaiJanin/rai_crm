<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import {
    store,
    update,
} from '@/actions/App/Http/Controllers/Crm/TicketController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import type {
    Company,
    Contact,
    Deal,
    Option,
    Ticket,
    TicketChannel,
    TicketPriority,
    TicketType,
    UserOption,
} from '@/types';

const props = defineProps<{
    ticket?: Ticket;
    contacts: Pick<Contact, 'contact_id' | 'full_name' | 'company_id'>[];
    companies: Pick<Company, 'company_id' | 'name'>[];
    deals: Pick<Deal, 'deal_id' | 'title' | 'contact_id'>[];
    users: UserOption[];
    types: Option<TicketType>[];
    priorities: Option<TicketPriority>[];
    channels: Option<TicketChannel>[];
    defaults?: {
        contact_id: number | null;
        company_id: number | null;
        deal_id: number | null;
    };
    submitLabel?: string;
}>();

const emit = defineEmits<{
    success: [];
}>();

const action = computed(() =>
    props.ticket ? update.form(props.ticket) : store.form(),
);

const contactId = ref<string | number | null>(
    props.ticket?.contact_id ?? props.defaults?.contact_id ?? '',
);
const companyId = ref<string | number | null>(
    props.ticket?.company_id ?? props.defaults?.company_id ?? '',
);
const dealId = ref<string | number | null>(
    props.ticket?.deal_id ?? props.defaults?.deal_id ?? '',
);

// Only offer the selected customer's deals.
const contactDeals = computed(() =>
    props.deals.filter((deal) => deal.contact_id === Number(contactId.value)),
);

watch(contactId, (id) => {
    const contact = props.contacts.find((c) => c.contact_id === Number(id));

    companyId.value = contact?.company_id ?? '';

    if (!contactDeals.value.some((d) => d.deal_id === Number(dealId.value))) {
        dealId.value = '';
    }
});
</script>

<template>
    <Form
        v-bind="action"
        :options="{ preserveScroll: true }"
        class="space-y-5"
        v-slot="{ errors, processing }"
        @success="emit('success')"
    >
        <div class="grid gap-2">
            <Label for="ticket-subject">Subject</Label>
            <Input
                id="ticket-subject"
                name="subject"
                placeholder="Short summary of the customer's concern"
                :default-value="ticket?.subject"
                required
                v-focus
            />
            <InputError :message="errors.subject" />
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="grid gap-2">
                <Label for="ticket-contact">Customer</Label>
                <NativeSelect
                    id="ticket-contact"
                    v-model="contactId"
                    name="contact_id"
                    required
                >
                    <option value="" disabled>Select a contact</option>
                    <option
                        v-for="contact in contacts"
                        :key="contact.contact_id"
                        :value="contact.contact_id"
                    >
                        {{ contact.full_name }}
                    </option>
                </NativeSelect>
                <InputError :message="errors.contact_id" />
            </div>
            <div class="grid gap-2">
                <Label for="ticket-company">Company</Label>
                <NativeSelect
                    id="ticket-company"
                    v-model="companyId"
                    name="company_id"
                >
                    <option value="">No company</option>
                    <option
                        v-for="company in companies"
                        :key="company.company_id"
                        :value="company.company_id"
                    >
                        {{ company.name }}
                    </option>
                </NativeSelect>
                <InputError :message="errors.company_id" />
            </div>
            <div class="grid gap-2">
                <Label for="ticket-deal">Related deal</Label>
                <NativeSelect id="ticket-deal" v-model="dealId" name="deal_id">
                    <option value="">
                        {{ contactDeals.length ? 'None' : 'No deals' }}
                    </option>
                    <option
                        v-for="deal in contactDeals"
                        :key="deal.deal_id"
                        :value="deal.deal_id"
                    >
                        {{ deal.title }}
                    </option>
                </NativeSelect>
                <InputError :message="errors.deal_id" />
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-4">
            <div class="grid gap-2">
                <Label for="ticket-type">Type</Label>
                <NativeSelect
                    id="ticket-type"
                    name="type"
                    :default-value="ticket?.type ?? 'question'"
                >
                    <option
                        v-for="type in types"
                        :key="type.value"
                        :value="type.value"
                    >
                        {{ type.label }}
                    </option>
                </NativeSelect>
                <InputError :message="errors.type" />
            </div>
            <div class="grid gap-2">
                <Label for="ticket-priority">Priority</Label>
                <NativeSelect
                    id="ticket-priority"
                    name="priority"
                    :default-value="ticket?.priority ?? 'medium'"
                >
                    <option
                        v-for="priority in priorities"
                        :key="priority.value"
                        :value="priority.value"
                    >
                        {{ priority.label }}
                    </option>
                </NativeSelect>
                <InputError :message="errors.priority" />
            </div>
            <div class="grid gap-2">
                <Label for="ticket-channel">Channel</Label>
                <NativeSelect
                    id="ticket-channel"
                    name="channel"
                    :default-value="ticket?.channel ?? 'email'"
                >
                    <option
                        v-for="channel in channels"
                        :key="channel.value"
                        :value="channel.value"
                    >
                        {{ channel.label }}
                    </option>
                </NativeSelect>
                <InputError :message="errors.channel" />
            </div>
            <div class="grid gap-2">
                <Label for="ticket-assignee">Assignee</Label>
                <NativeSelect
                    id="ticket-assignee"
                    name="assignee_id"
                    :default-value="
                        ticket
                            ? (ticket.assignee_id ?? '')
                            : $page.props.auth.user.id
                    "
                >
                    <option value="">Unassigned</option>
                    <option
                        v-for="user in users"
                        :key="user.id"
                        :value="user.id"
                    >
                        {{ user.name }}
                    </option>
                </NativeSelect>
                <InputError :message="errors.assignee_id" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="ticket-description">Description</Label>
            <Textarea
                id="ticket-description"
                name="description"
                rows="6"
                placeholder="What happened, what the customer expects, steps already tried…"
                :default-value="ticket?.description"
                required
            />
            <InputError :message="errors.description" />
        </div>

        <div class="flex items-center justify-end gap-2">
            <slot name="cancel" />
            <Button type="submit" :disabled="processing">
                {{ submitLabel ?? 'Save ticket' }}
            </Button>
        </div>
    </Form>
</template>
