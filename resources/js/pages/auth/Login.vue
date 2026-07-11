<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type LoginCredentialHint = {
    label: string;
    username: string;
    password: string;
};

const props = defineProps<{
    status?: string;
    fail?: string;
    canResetPassword: boolean;
    legacyApplicationTitle?: string;
    loginCredentialHint?: LoginCredentialHint | null;
}>();

const page = usePage();
const csrfToken = page.props.csrfToken as string;
</script>

<template>
    <Head title="Log in" />

    <form
        class="flex flex-col gap-6"
        action="/login-user"
        method="POST"
        autocomplete="off"
        data-test="legacy-login-form"
    >
        <input type="hidden" name="_token" :value="csrfToken" />

        <h1 class="text-2xl font-semibold">
            {{ props.legacyApplicationTitle || 'Centralized Automated Meter Reading' }}
        </h1>

        <div v-if="status" class="text-center text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <div
            v-if="fail"
            class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
            role="status"
        >
            {{ fail }}
        </div>

        <div
            v-if="props.loginCredentialHint"
            class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 shadow-sm dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-100"
            role="note"
            data-test="login-credential-hint"
        >
            <p class="font-semibold">{{ props.loginCredentialHint.label }}</p>
            <dl class="mt-2 grid gap-1">
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-amber-800 dark:text-amber-200">Username</dt>
                    <dd>
                        <code class="rounded bg-white/80 px-2 py-0.5 font-mono text-amber-950 dark:bg-black/30 dark:text-amber-50">
                            {{ props.loginCredentialHint.username }}
                        </code>
                    </dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-amber-800 dark:text-amber-200">Password</dt>
                    <dd>
                        <code class="rounded bg-white/80 px-2 py-0.5 font-mono text-amber-950 dark:bg-black/30 dark:text-amber-50">
                            {{ props.loginCredentialHint.password }}
                        </code>
                    </dd>
                </div>
            </dl>
        </div>

        <div class="grid gap-2">
            <Label for="user_name">Username</Label>
            <Input id="user_name" type="text" name="user_name" required autofocus />
        </div>

        <div class="grid gap-2">
            <Label for="InputPassword">Password</Label>
            <PasswordInput id="InputPassword" name="InputPassword" required />
        </div>

        <Button class="w-full" type="submit" data-test="login-button">
            Login
        </Button>

        <div class="text-center text-sm text-muted-foreground">
            <a href="/passwordreset">Reset Password</a>
        </div>

        <a
            v-if="canResetPassword"
            href="/forgot-password"
            class="text-sm text-muted-foreground underline"
        >
            Modern reset form
        </a>
    </form>
</template>
