<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';

type Building = {
    building_id: number;
    site_idx: number;
    building_code: string | null;
    building_description: string | null;
};

type Row = {
    building_id: number;
    site_idx: number;
    building_code: string | null;
    building_description: string | null;
    action?: string;
};

const props = defineProps<{
    buildings: Building[] | Row[];
    title: string;
}>();

const page = usePage();
const csrfToken = page.props.csrfToken as string;
</script>

<template>
    <Head :title="title" />

    <div class="space-y-6">
        <h1 class="text-2xl font-semibold">{{ title }}</h1>

        <form class="flex flex-wrap gap-3" method="POST" action="/create_building_post">
            <input type="hidden" name="_token" :value="csrfToken" />

            <label class="flex flex-col gap-1">
                <span class="text-sm font-medium">Site ID</span>
                <input
                    class="h-9 rounded-md border px-3"
                    type="text"
                    name="siteID"
                    placeholder="1"
                />
            </label>

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
                    placeholder="Accessible Building"
                />
            </label>

            <button class="rounded-md bg-black px-4 py-2 text-sm font-medium text-white" type="submit">
                Create
            </button>
        </form>

        <table class="w-full border-collapse">
            <thead>
                <tr>
                    <th class="border px-3 py-2 text-left">Building ID</th>
                    <th class="border px-3 py-2 text-left">Site ID</th>
                    <th class="border px-3 py-2 text-left">Building Code</th>
                    <th class="border px-3 py-2 text-left">Description</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="building in props.buildings" :key="building.building_id">
                    <td class="border px-3 py-2">{{ building.building_id }}</td>
                    <td class="border px-3 py-2">{{ building.site_idx }}</td>
                    <td class="border px-3 py-2">{{ building.building_code || '-' }}</td>
                    <td class="border px-3 py-2">{{ building.building_description || '-' }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
