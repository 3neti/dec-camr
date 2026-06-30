<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    status?: string;
    error?: string;
    legacyApplicationTitle?: string;
}>();

const page = usePage();
const csrfToken = page.props.csrfToken as string;

const email = ref('');
const processing = ref(false);
const successMessage = ref('');
const validationError = ref('');

const submit = async () => {
    processing.value = true;
    validationError.value = '';
    successMessage.value = '';

    const response = await fetch('/reset-password', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            Accept: 'application/json',
        },
        body: JSON.stringify({ user_email_address: email.value }),
    });

    const payload = await response.json();

    processing.value = false;

    if (response.status === 422) {
        validationError.value = payload.errors?.user_email_address?.[0] ?? 'Request failed';

        return;
    }

    if (!response.ok) {
        validationError.value = 'Request failed';

        return;
    }

    successMessage.value = payload.success;

    if (response.status === 200) {
        email.value = '';
    }
};
</script>

<template>
    <Head title="Forgot password" />

    <div class="flex flex-col gap-4">
        <h1 class="text-2xl font-semibold">
            {{ props.legacyApplicationTitle || 'Centralized Automated Meter Reading' }}
        </h1>

        <p>Please Enter your Email Address Registered to your CAMR User Account</p>

        <div v-if="status || successMessage" class="text-sm font-medium text-green-600">
            {{ status || successMessage }}
        </div>

        <div v-if="error || validationError" class="text-sm font-medium text-red-600">
            {{ error || validationError }}
        </div>

        <form class="grid gap-3" @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="user_email_address">Email Address</Label>
                <Input
                    id="user_email_address"
                    type="email"
                    name="user_email_address"
                    autocomplete="off"
                    autofocus
                    placeholder="Email Address"
                    v-model="email"
                />
            </div>

            <Button
                id="check-email"
                type="submit"
                class="w-full"
                :disabled="processing"
                data-test="email-password-reset-link-button"
            >
                Send
            </Button>
        </form>

        <div class="space-x-1 text-center text-sm text-muted-foreground">
            <span>Back to</span>
            <a href="/">Login</a>
        </div>
    </div>
</template>
