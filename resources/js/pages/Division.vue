<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';

type Division = {
    division_id: number;
    division_name: string;
    division_code: string | null;
};

const props = defineProps<{
    divisions: Division[];
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
            action="/create_division_post"
        >
            <input type="hidden" name="_token" :value="csrfToken" />

            <label class="flex flex-col gap-1">
                <span class="text-sm font-medium">Division Code</span>
                <input
                    class="h-9 rounded-md border px-3"
                    type="text"
                    name="division_code"
                    placeholder="Division Code"
                />
            </label>

            <label class="flex flex-col gap-1">
                <span class="text-sm font-medium">Division Name</span>
                <input
                    class="h-9 rounded-md border px-3"
                    type="text"
                    name="division_name"
                    placeholder="Division Name"
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
                <tr v-for="division in props.divisions" :key="division.division_id">
                    <td class="border px-3 py-2">{{ division.division_code || '-' }}</td>
                    <td class="border px-3 py-2">{{ division.division_name }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
