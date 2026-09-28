<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    store,
    update,
} from '@/actions/App/Http/Controllers/Crm/TaskController';
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
import { Textarea } from '@/components/ui/textarea';
import type {
    Contact,
    Deal,
    Option,
    Task,
    TaskStatus,
    UserOption,
} from '@/types';

const props = defineProps<{
    task?: Task;
    users: UserOption[];
    statuses: Option<TaskStatus>[];
    /** Selectable deals; when omitted the deal is fixed to `dealId`. */
    deals?: Pick<Deal, 'deal_id' | 'title'>[];
    /** Selectable contacts; when omitted the contact is fixed to `contactId`. */
    contacts?: Pick<Contact, 'contact_id' | 'full_name'>[];
    dealId?: number;
    contactId?: number;
    ticketId?: number;
}>();

const open = ref(false);
const action = computed(() =>
    props.task ? update.form(props.task) : store.form(),
);
const dealValue = computed(() => props.task?.deal_id ?? props.dealId ?? '');
const contactValue = computed(
    () => props.task?.contact_id ?? props.contactId ?? '',
);
const ticketValue = computed(
    () => props.task?.ticket_id ?? props.ticketId ?? '',
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
                        {{ task ? 'Edit task' : 'New task' }}
                    </DialogTitle>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="task-description">Description</Label>
                    <Textarea
                        id="task-description"
                        name="description"
                        rows="3"
                        :default-value="task?.description"
                        required
                        v-focus
                    />
                    <InputError :message="errors.description" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="task-responsible">Assigned to</Label>
                        <NativeSelect
                            id="task-responsible"
                            name="responsible_person_id"
                            :default-value="
                                task?.responsible_person_id ??
                                $page.props.auth.user.id
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
                        <InputError :message="errors.responsible_person_id" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="task-due">Due date</Label>
                        <Input
                            id="task-due"
                            name="due_date"
                            type="date"
                            :default-value="task?.due_date ?? ''"
                        />
                        <InputError :message="errors.due_date" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="task-status">Status</Label>
                    <NativeSelect
                        id="task-status"
                        name="status"
                        :default-value="task?.status ?? 'pending'"
                    >
                        <option
                            v-for="status in statuses"
                            :key="status.value"
                            :value="status.value"
                        >
                            {{ status.label }}
                        </option>
                    </NativeSelect>
                    <InputError :message="errors.status" />
                </div>

                <div v-if="deals || contacts" class="grid gap-4 sm:grid-cols-2">
                    <div v-if="deals" class="grid gap-2">
                        <Label for="task-deal">Deal</Label>
                        <NativeSelect
                            id="task-deal"
                            name="deal_id"
                            :default-value="dealValue"
                        >
                            <option value="">No deal</option>
                            <option
                                v-for="deal in deals"
                                :key="deal.deal_id"
                                :value="deal.deal_id"
                            >
                                {{ deal.title }}
                            </option>
                        </NativeSelect>
                        <InputError :message="errors.deal_id" />
                    </div>
                    <div v-if="contacts" class="grid gap-2">
                        <Label for="task-contact">Contact</Label>
                        <NativeSelect
                            id="task-contact"
                            name="contact_id"
                            :default-value="contactValue"
                        >
                            <option value="">
                                {{ deals ? "Deal's contact" : 'No contact' }}
                            </option>
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
                </div>

                <input
                    v-if="!deals"
                    type="hidden"
                    name="deal_id"
                    :value="dealValue"
                />
                <input
                    v-if="!contacts"
                    type="hidden"
                    name="contact_id"
                    :value="contactValue"
                />
                <input type="hidden" name="ticket_id" :value="ticketValue" />

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
