<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';

type Meter = {
    meter_id: number;
    meter_name: string;
    meter_default_name: string;
    meter_status: string;
};

const props = defineProps<{
    meters?: Meter[];
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
            action="/create_meter_post"
        >
            <input type="hidden" name="_token" :value="csrfToken" />

            <label class="flex flex-col gap-1">
                <span class="text-sm font-medium">Meter Name</span>
                <input
                    class="h-9 rounded-md border px-3"
                    type="text"
                    name="meter_name"
                    placeholder="MTR-001"
                />
            </label>

            <label class="flex flex-col gap-1">
                <span class="text-sm font-medium">Alternate Address</span>
                <input
                    class="h-9 rounded-md border px-3"
                    type="text"
                    name="meter_default_name"
                    placeholder="Alternate Name"
                />
            </label>

            <label class="flex flex-col gap-1">
                <span class="text-sm font-medium">Configuration</span>
                <input
                    class="h-9 rounded-md border px-3"
                    type="text"
                    name="meter_model_id"
                    placeholder="1"
                />
            </label>

            <label class="flex flex-col gap-1">
                <span class="text-sm font-medium">Gateway ID</span>
                <input
                    class="h-9 rounded-md border px-3"
                    type="text"
                    name="rtu_sn_number_id"
                    placeholder="1"
                />
            </label>

            <label class="flex flex-col gap-1">
                <span class="text-sm font-medium">Location ID</span>
                <input
                    class="h-9 rounded-md border px-3"
                    type="text"
                    name="location_id"
                    placeholder="1"
                />
            </label>

            <input type="hidden" name="siteID" value="1" />
            <input type="hidden" name="site_code" value="SITEA" />
            <input type="hidden" name="meter_name_addressable" value="1" />
            <input type="hidden" name="meter_multiplier" value="1" />
            <input type="hidden" name="meter_type" value="Power" />
            <input type="hidden" name="meter_brand" value="Schneider" />
            <input type="hidden" name="meter_role" value="Client Meter" />
            <input type="hidden" name="meter_status" value="ACTIVE" />
            <input type="hidden" name="customer_name" value="" />
            <input type="hidden" name="meter_remarks" value="" />

            <button class="rounded-md bg-black px-4 py-2 text-sm font-medium text-white" type="submit">
                Create
            </button>
        </form>

        <table class="w-full border-collapse">
            <thead>
                <tr>
                    <th class="border px-3 py-2 text-left">Name</th>
                    <th class="border px-3 py-2 text-left">Alternate</th>
                    <th class="border px-3 py-2 text-left">Status</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="meter in props.meters ?? []" :key="meter.meter_id">
                    <td class="border px-3 py-2">{{ meter.meter_name }}</td>
                    <td class="border px-3 py-2">{{ meter.meter_default_name }}</td>
                    <td class="border px-3 py-2">{{ meter.meter_status }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
