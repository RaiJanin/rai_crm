<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    store,
    update,
} from '@/actions/App/Http/Controllers/Crm/ContactController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import type { Company, Contact } from '@/types';

const props = defineProps<{
    contact?: Contact;
    companies: Pick<Company, 'company_id' | 'name'>[];
    companyId?: number;
}>();

const open = ref(false);
const action = computed(() =>
    props.contact ? update.form(props.contact) : store.form(),
);
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <slot />
        </DialogTrigger>
        <DialogContent class="sm:max-w-lg">
            <Form
                v-bind="action"
                :options="{ preserveScroll: true }"
                class="space-y-5"
                v-slot="{ errors, processing }"
                @success="open = false"
            >
                <DialogHeader>
                    <DialogTitle>
                        {{ contact ? 'Edit contact' : 'New contact' }}
                    </DialogTitle>
                </DialogHeader>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="contact-first-name">First name</Label>
                        <Input
                            id="contact-first-name"
                            name="first_name"
                            :default-value="contact?.first_name"
                            required
                            v-focus
                        />
                        <InputError :message="errors.first_name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="contact-last-name">Last name</Label>
                        <Input
                            id="contact-last-name"
                            name="last_name"
                            :default-value="contact?.last_name ?? ''"
                        />
                        <InputError :message="errors.last_name" />
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="contact-email">Email</Label>
                        <Input
                            id="contact-email"
                            name="email"
                            type="email"
                            :default-value="contact?.email ?? ''"
                        />
                        <InputError :message="errors.email" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="contact-phone">Phone</Label>
                        <Input
                            id="contact-phone"
                            name="phone"
                            :default-value="contact?.phone ?? ''"
                        />
                        <InputError :message="errors.phone" />
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="contact-company">Company</Label>
                        <NativeSelect
                            id="contact-company"
                            name="company_id"
                            :default-value="
                                contact?.company_id ?? companyId ?? ''
                            "
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
                        <Label for="contact-title">Job title</Label>
                        <Input
                            id="contact-title"
                            name="title"
                            :default-value="contact?.title ?? ''"
                        />
                        <InputError :message="errors.title" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="contact-source">Source</Label>
                    <Input
                        id="contact-source"
                        name="source"
                        placeholder="Referral, website, event…"
                        :default-value="contact?.source ?? ''"
                    />
                    <InputError :message="errors.source" />
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary" type="button"
                            >Cancel</Button
                        >
                    </DialogClose>
                    <Button type="submit" :disabled="processing">Save</Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
