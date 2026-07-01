<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import OperatorPage from '@/components/operator/OperatorPage.vue';

type UserRow = {
    user_id: number;
    user_real_name: string;
    user_job_title: string | null;
    user_name: string;
    user_email_address: string | null;
    user_type: string | null;
    user_access: string | null;
};

const props = defineProps<{
    users: UserRow[];
    title: string;
}>();

const page = usePage();
const csrfToken = page.props.csrfToken as string;
</script>

<template>
    <OperatorPage :title="title">
        <form class="flex flex-wrap gap-3" method="POST" action="/create_user_post">
            <input type="hidden" name="_token" :value="csrfToken" />

            <label class="flex flex-col gap-1">
                <span class="text-sm font-medium">Full Name</span>
                <input class="h-9 rounded-md border px-3" type="text" name="user_real_name" placeholder="Name" />
            </label>

            <label class="flex flex-col gap-1">
                <span class="text-sm font-medium">Username</span>
                <input class="h-9 rounded-md border px-3" type="text" name="user_name" placeholder="username" />
            </label>

            <label class="flex flex-col gap-1">
                <span class="text-sm font-medium">Email Address</span>
                <input class="h-9 rounded-md border px-3" type="email" name="user_email_address" placeholder="name@example.test" />
            </label>

            <label class="flex flex-col gap-1">
                <span class="text-sm font-medium">Password</span>
                <input class="h-9 rounded-md border px-3" type="password" name="user_password" />
            </label>

            <label class="flex flex-col gap-1">
                <span class="text-sm font-medium">Job Title</span>
                <input class="h-9 rounded-md border px-3" type="text" name="user_job_title" placeholder="Engineer" />
            </label>

            <label class="flex flex-col gap-1">
                <span class="text-sm font-medium">User Type</span>
                <input class="h-9 rounded-md border px-3" type="text" name="user_type" value="User" />
            </label>

            <input type="hidden" name="user_access" value="Selected" />

            <button class="rounded-md bg-black px-4 py-2 text-sm font-medium text-white" type="submit">
                Create
            </button>
        </form>

        <table class="w-full border-collapse">
            <thead>
                <tr>
                    <th class="border px-3 py-2 text-left">Real Name</th>
                    <th class="border px-3 py-2 text-left">Username</th>
                    <th class="border px-3 py-2 text-left">Email</th>
                    <th class="border px-3 py-2 text-left">Type</th>
                    <th class="border px-3 py-2 text-left">Scope</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="user in props.users" :key="user.user_id">
                    <td class="border px-3 py-2">{{ user.user_real_name }}</td>
                    <td class="border px-3 py-2">{{ user.user_name }}</td>
                    <td class="border px-3 py-2">{{ user.user_email_address || '-' }}</td>
                    <td class="border px-3 py-2">{{ user.user_type || '-' }}</td>
                    <td class="border px-3 py-2">{{ user.user_access || '-' }}</td>
                </tr>
            </tbody>
        </table>
    </OperatorPage>
</template>
