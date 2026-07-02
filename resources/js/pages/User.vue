<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import EntityTable from '@/components/operator/EntityTable.vue';
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

const columns = [
    {
        key: 'user_real_name',
        label: 'Real Name',
    },
    {
        key: 'user_name',
        label: 'Username',
    },
    {
        key: 'user_email_address',
        label: 'Email',
        render: (value: unknown) => String(value ?? '-'),
    },
    {
        key: 'user_type',
        label: 'Type',
        render: (value: unknown) => String(value ?? '-'),
    },
    {
        key: 'user_access',
        label: 'Scope',
        render: (value: unknown) => String(value ?? '-'),
    },
];
</script>

<template>
    <OperatorPage :title="title">
        <form class="grid gap-4 rounded-xl border bg-card p-5 shadow-sm sm:grid-cols-2 xl:grid-cols-4" method="POST" action="/create_user_post">
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

            <div class="sm:col-span-2 xl:col-span-4">
                <button class="inline-flex w-full items-center justify-center rounded-md bg-black px-4 py-2 text-sm font-medium text-white sm:w-auto" type="submit">
                    Create
                </button>
            </div>
        </form>

        <EntityTable
            title="Existing users"
            description="Current operator accounts and access scope."
            :columns="columns"
            :rows="props.users"
            row-key="user_id"
        />
    </OperatorPage>
</template>
