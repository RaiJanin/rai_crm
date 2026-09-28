<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    store,
    update,
} from '@/actions/App/Http/Controllers/Crm/CompanyController';
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
import type { Company } from '@/types';

const props = defineProps<{
    company?: Company;
}>();

const open = ref(false);
const action = computed(() =>
    props.company ? update.form(props.company) : store.form(),
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
                        {{ company ? 'Edit company' : 'New company' }}
                    </DialogTitle>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="company-name">Name</Label>
                    <Input
                        id="company-name"
                        name="name"
                        :default-value="company?.name"
                        required
                        v-focus
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="company-industry">Industry</Label>
                        <Input
                            id="company-industry"
                            name="industry"
                            :default-value="company?.industry ?? ''"
                        />
                        <InputError :message="errors.industry" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="company-phone">Phone</Label>
                        <Input
                            id="company-phone"
                            name="phone"
                            :default-value="company?.phone ?? ''"
                        />
                        <InputError :message="errors.phone" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="company-website">Website</Label>
                    <Input
                        id="company-website"
                        name="website"
                        type="url"
                        placeholder="https://"
                        :default-value="company?.website ?? ''"
                    />
                    <InputError :message="errors.website" />
                </div>

                <div class="grid gap-2">
                    <Label for="company-address">Address</Label>
                    <Input
                        id="company-address"
                        name="address"
                        :default-value="company?.address ?? ''"
                    />
                    <InputError :message="errors.address" />
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
