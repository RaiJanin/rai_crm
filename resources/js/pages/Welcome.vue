<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, login } from '@/routes';
import { register } from '@/routes';

defineProps({
    canLogin: { type: Boolean, default: true },
    canRegister: { type: Boolean, default: true },
});

const stages = [
    { name: 'Lead', deals: ['Northline Retail', 'Abello & Co.'] },
    { name: 'Qualified', deals: ['Cebu Freight'] },
    { name: 'Proposal', deals: ['Marbella Homes', 'Tan Hardware'] },
    { name: 'Won', deals: ['Isla Café'] },
];

const appName = import.meta.env.VITE_APP_NAME || "CRM"
</script>

<template>
    <Head :title="appName" />

    <div class="min-h-screen bg-[#F9FAFB] text-[#1F2937] antialiased">
        <!-- Nav -->
        <header class="mx-auto flex max-w-6xl items-center justify-between px-6 py-6">
            <div class="flex items-center gap-2">
                <span class="grid h-8 w-8 place-items-center rounded-md bg-[#2563EB] text-sm font-semibold text-white">R</span>
                <span class="text-lg font-semibold tracking-tight">{{ appName }}</span>
            </div>

            <nav v-if="canLogin" class="flex items-center gap-6 text-sm">
                <Link v-if="$page.props.auth.user" :href="dashboard()" class="font-medium text-[#1F2937] hover:text-[#2563EB]">
                    Go to dashboard
                </Link>
                <template v-else>
                    <Link :href="login()" class="font-medium text-[#1F2937] hover:text-[#2563EB]">
                        Log in
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="register()"
                        class="rounded-md bg-[#2563EB] px-4 py-2 font-medium text-white hover:bg-[#1D4ED8]"
                    >
                        Get started
                    </Link>
                </template>
            </nav>
        </header>

        <!-- Hero -->
        <main class="mx-auto max-w-6xl px-6">
            <section class="grid items-center gap-16 py-16 md:grid-cols-2 md:py-24">
                <div class="max-w-md">
                    <h1 class="text-4xl font-semibold leading-tight tracking-tight text-[#1F2937] md:text-5xl">
                        See every deal move, from first call to closed.
                    </h1>
                    <p class="mt-5 text-[17px] leading-relaxed text-[#6B7280]">
                        {{ appName }} keeps contacts, deals, and follow-ups in one place, so nothing waits on someone's memory.
                    </p>
                    <div class="mt-8 flex items-center gap-4">
                        <Link
                            v-if="canRegister"
                            :href="register()"
                            class="rounded-md bg-[#2563EB] px-5 py-3 text-sm font-medium text-white hover:bg-[#1D4ED8]"
                        >
                            Create your workspace
                        </Link>
                        <Link v-if="canLogin" :href="login()" class="text-sm font-medium text-[#1F2937] hover:text-[#2563EB]">
                            I already have an account
                        </Link>
                    </div>
                </div>

                <!-- Pipeline mockup -->
                <div class="rounded-xl border border-[#DBEAFE] bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-center justify-between">
                        <span class="text-sm font-medium text-[#1F2937]">Pipeline</span>
                        <span class="rounded bg-[#FEF3C7] px-2 py-0.5 text-xs font-medium text-[#92400E]">3 need follow-up</span>
                    </div>
                    <div class="grid grid-cols-4 gap-3">
                        <div v-for="stage in stages" :key="stage.name">
                            <p class="mb-2 text-xs font-medium text-[#6B7280]">{{ stage.name }}</p>
                            <div class="space-y-2">
                                <div
                                    v-for="deal in stage.deals"
                                    :key="deal"
                                    class="rounded-md border border-[#EFF6FF] bg-[#F9FAFB] px-2 py-2 text-xs leading-snug text-[#1F2937]"
                                >
                                    {{ deal }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Feature strip -->
            <section class="grid gap-10 border-t border-[#DBEAFE] py-16 md:grid-cols-3 md:gap-8">
                <div>
                    <h3 class="font-medium text-[#1F2937]">Contacts and companies</h3>
                    <p class="mt-2 text-sm leading-relaxed text-[#6B7280]">
                        Every person and account in one record, with the full history of what's been said and sent.
                    </p>
                </div>
                <div>
                    <h3 class="font-medium text-[#1F2937]">A pipeline you can see</h3>
                    <p class="mt-2 text-sm leading-relaxed text-[#6B7280]">
                        Deals move through stages on a board, so it's obvious what's stalled and what's close.
                    </p>
                </div>
                <div>
                    <h3 class="font-medium text-[#1F2937]">Follow-ups that don't get lost</h3>
                    <p class="mt-2 text-sm leading-relaxed text-[#6B7280]">
                        Tasks stay attached to the deal or contact they belong to, with an owner and a due date.
                    </p>
                </div>
            </section>
        </main>

        <footer class="mx-auto max-w-6xl px-6 py-10 text-xs text-[#6B7280]">
            {{ appName }}
        </footer>
    </div>
</template>