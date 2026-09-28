<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import {
    store,
    update,
} from '@/actions/App/Http/Controllers/Crm/DealController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import type { Company, Contact, Deal, DealStage, Option } from '@/types';

const props = defineProps<{
    deal?: Deal;
    contacts: Pick<Contact, 'contact_id' | 'full_name' | 'company_id'>[];
    companies: Pick<Company, 'company_id' | 'name'>[];
    stages: Option<DealStage>[];
    defaults?: { contact_id: number | null; company_id: number | null };
    submitLabel?: string;
}>();

const emit = defineEmits<{
    success: [];
}>();

const action = computed(() =>
    props.deal ? update.form(props.deal) : store.form(),
);

const contactId = ref<string | number | null>(
    props.deal?.contact_id ?? props.defaults?.contact_id ?? '',
);
const companyId = ref<string | number | null>(
    props.deal?.company_id ?? props.defaults?.company_id ?? '',
);

// Picking a contact pre-fills their company when none is chosen yet.
watch(contactId, (id) => {
    const contact = props.contacts.find((c) => c.contact_id === Number(id));

    if (contact?.company_id && !companyId.value) {
        companyId.value = contact.company_id;
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
            <Label for="deal-title">Title</Label>
            <Input
                id="deal-title"
                name="title"
                :default-value="deal?.title"
                required
                v-focus
            />
            <InputError :message="errors.title" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="deal-contact">Contact</Label>
                <NativeSelect
                    id="deal-contact"
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
                <Label for="deal-company">Company</Label>
                <NativeSelect
                    id="deal-company"
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
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="grid gap-2">
                <Label for="deal-value">Value</Label>
                <Input
                    id="deal-value"
                    name="value"
                    type="number"
                    min="0"
                    step="0.01"
                    :default-value="deal?.value ?? '0'"
                    required
                />
                <InputError :message="errors.value" />
            </div>
            <div class="grid gap-2">
                <Label for="deal-stage">Stage</Label>
                <NativeSelect
                    id="deal-stage"
                    name="stage"
                    :default-value="deal?.stage ?? 'lead'"
                >
                    <option
                        v-for="stage in stages"
                        :key="stage.value"
                        :value="stage.value"
                    >
                        {{ stage.label }}
                    </option>
                </NativeSelect>
                <InputError :message="errors.stage" />
            </div>
            <div class="grid gap-2">
                <Label for="deal-close">Expected close</Label>
                <Input
                    id="deal-close"
                    name="expected_close_date"
                    type="date"
                    :default-value="deal?.expected_close_date ?? ''"
                />
                <InputError :message="errors.expected_close_date" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="deal-notes">Notes</Label>
            <Textarea
                id="deal-notes"
                name="notes"
                rows="4"
                :default-value="deal?.notes ?? ''"
            />
            <InputError :message="errors.notes" />
        </div>

        <div class="flex items-center justify-end gap-2">
            <slot name="cancel" />
            <Button type="submit" :disabled="processing">
                {{ submitLabel ?? 'Save deal' }}
            </Button>
        </div>
    </Form>
</template>
