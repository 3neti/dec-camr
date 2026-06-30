<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';

type Gateway = {
    rtu_id: number;
    gateway_sn: string;
    gateway_mac: string;
    gateway_ip: string;
    site_code: string | null;
};

const props = defineProps<{
    gateways: Gateway[];
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
            action="/create_gateway_post"
        >
            <input type="hidden" name="_token" :value="csrfToken" />

            <label class="flex flex-col gap-1">
                <span class="text-sm font-medium">Gateway Serial Number</span>
                <input
                    class="h-9 rounded-md border px-3"
                    type="text"
                    name="gateway_sn"
                    placeholder="GW-001"
                />
            </label>

            <label class="flex flex-col gap-1">
                <span class="text-sm font-medium">MAC Address</span>
                <input
                    class="h-9 rounded-md border px-3"
                    type="text"
                    name="gateway_mac"
                    placeholder="AA:BB:CC:DD:EE:FF"
                />
            </label>

            <label class="flex flex-col gap-1">
                <span class="text-sm font-medium">IP Address</span>
                <input
                    class="h-9 rounded-md border px-3"
                    type="text"
                    name="gateway_ip"
                    placeholder="10.0.0.1"
                />
            </label>

            <input type="hidden" name="siteID" value="1" />
            <input type="hidden" name="site_code" value="SITEA" />
            <input type="hidden" name="connection_type" value="LAN" />
            <input type="hidden" name="location_id" value="0" />

            <button class="rounded-md bg-black px-4 py-2 text-sm font-medium text-white" type="submit">
                Create
            </button>
        </form>

        <table class="w-full border-collapse">
            <thead>
                <tr>
                    <th class="border px-3 py-2 text-left">Serial</th>
                    <th class="border px-3 py-2 text-left">MAC</th>
                    <th class="border px-3 py-2 text-left">IP</th>
                    <th class="border px-3 py-2 text-left">Site Code</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="gateway in props.gateways" :key="gateway.rtu_id">
                    <td class="border px-3 py-2">{{ gateway.gateway_sn }}</td>
                    <td class="border px-3 py-2">{{ gateway.gateway_mac }}</td>
                    <td class="border px-3 py-2">{{ gateway.gateway_ip }}</td>
                    <td class="border px-3 py-2">{{ gateway.site_code || '—' }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
