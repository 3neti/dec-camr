<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';

type Company = {
    company_id: number;
    company_name: string;
    company_code: string | null;
};

const props = defineProps<{
    companies: Company[];
    title: string;
}>();

const page = usePage();
const csrfToken = page.props.csrfToken as string;
</script>

<template>
    <Head :title="title" />

    <div class="space-y-6">
        <h1 class="text-2xl font-semibold">{{ title }}</h1>

        <form
            class="flex flex-wrap gap-3"
            method="POST"
            action="/create_company_post"
        >
            <input type="hidden" name="_token" :value="csrfToken" />

            <label class="flex flex-col gap-1">
                <span class="text-sm font-medium">Company Name</span>
                <input
                    class="h-9 rounded-md border px-3"
                    type="text"
                    name="company_name"
                    placeholder="Company Name"
                />
            </label>

            <button class="rounded-md bg-black px-4 py-2 text-sm font-medium text-white" type="submit">
                Create
            </button>
        </form>

        <table class="w-full border-collapse">
            <thead>
                <tr>
                    <th class="border px-3 py-2 text-left">Code</th>
                    <th class="border px-3 py-2 text-left">Name</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="company in props.companies" :key="company.company_id">
                    <td class="border px-3 py-2">{{ company.company_code || '-' }}</td>
                    <td class="border px-3 py-2">{{ company.company_name }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
