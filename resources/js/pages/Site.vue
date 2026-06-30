<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';

type Site = {
    site_id: number;
    site_code: string | null;
    building_code: string | null;
    building_description: string | null;
    division_idx: number;
    company_idx: number;
};

type ViewSite = {
    site_id: number;
    site_code: string | null;
    building_description: string | null;
    division_idx: number;
    company_idx: number;
};

const props = defineProps<{
    sites: Site[];
    title: string;
    viewSite?: ViewSite | null;
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
            action="/create_site_post"
        >
            <input type="hidden" name="_token" :value="csrfToken" />

            <label class="flex flex-col gap-1">
                <span class="text-sm font-medium">Building Code</span>
                <input
                    class="h-9 rounded-md border px-3"
                    type="text"
                    name="building_code"
                    placeholder="SITEA"
                />
            </label>

            <label class="flex flex-col gap-1">
                <span class="text-sm font-medium">Building Description</span>
                <input
                    class="h-9 rounded-md border px-3"
                    type="text"
                    name="building_description"
                    placeholder="Building Description"
                />
            </label>

            <label class="flex flex-col gap-1">
                <span class="text-sm font-medium">Division ID</span>
                <input
                    class="h-9 rounded-md border px-3"
                    type="text"
                    name="division_id"
                    placeholder="1"
                />
            </label>

            <label class="flex flex-col gap-1">
                <span class="text-sm font-medium">Company ID</span>
                <input
                    class="h-9 rounded-md border px-3"
                    type="text"
                    name="company_id"
                    placeholder="1"
                />
            </label>

            <button class="rounded-md bg-black px-4 py-2 text-sm font-medium text-white" type="submit">
                Create
            </button>
        </form>

        <div v-if="props.viewSite" class="rounded border p-4">
            <h2 class="text-lg font-medium">Viewing Site #{{ props.viewSite.site_id }}</h2>
            <p class="text-sm text-gray-700">Code: {{ props.viewSite.site_code || '—' }}</p>
            <p class="text-sm text-gray-700">Description: {{ props.viewSite.building_description || '—' }}</p>
        </div>

        <table class="w-full border-collapse">
            <thead>
                <tr>
                    <th class="border px-3 py-2 text-left">Building Code</th>
                    <th class="border px-3 py-2 text-left">Description</th>
                    <th class="border px-3 py-2 text-left">Division ID</th>
                    <th class="border px-3 py-2 text-left">Company ID</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="site in props.sites" :key="site.site_id">
                    <td class="border px-3 py-2">{{ site.building_code || site.site_code || '—' }}</td>
                    <td class="border px-3 py-2">{{ site.building_description || '—' }}</td>
                    <td class="border px-3 py-2">{{ site.division_idx }}</td>
                    <td class="border px-3 py-2">{{ site.company_idx }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
