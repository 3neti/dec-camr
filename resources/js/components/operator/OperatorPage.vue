<script setup lang="ts">
import { router, Head } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import OperatorPageLoading from '@/components/operator/OperatorPageLoading.vue';

const props = defineProps<{
    title: string;
    heading?: string;
    description?: string;
}>();

const isLoading = ref(false);
let loadingTimeout: ReturnType<typeof setTimeout> | null = null;
let removeStartListener: (() => void) | null = null;
let removeFinishListener: (() => void) | null = null;

const clearLoadingTimeout = (): void => {
    if (loadingTimeout !== null) {
        clearTimeout(loadingTimeout);
        loadingTimeout = null;
    }
};

onMounted(() => {
    removeStartListener = router.on('start', () => {
        clearLoadingTimeout();

        loadingTimeout = setTimeout(() => {
            isLoading.value = true;
        }, 200);
    });

    removeFinishListener = router.on('finish', () => {
        clearLoadingTimeout();
        isLoading.value = false;
    });
});

onBeforeUnmount(() => {
    clearLoadingTimeout();
    removeStartListener?.();
    removeFinishListener?.();
});
</script>

<template>
    <Head :title="props.title" />

    <div class="space-y-6 px-4 py-1 sm:px-6" :aria-busy="isLoading">
        <div class="space-y-2">
            <h1 class="text-2xl font-semibold">{{ props.heading ?? props.title }}</h1>
            <p v-if="props.description" class="text-sm text-muted-foreground">{{ props.description }}</p>
            <p
                v-if="isLoading"
                class="text-xs font-medium uppercase tracking-wide text-muted-foreground"
                aria-live="polite"
            >
                Refreshing operator workspace…
            </p>
        </div>

        <div class="relative min-h-40">
            <div :class="isLoading ? 'pointer-events-none opacity-40 transition-opacity duration-150' : 'transition-opacity duration-150'">
                <slot />
            </div>

            <div
                v-if="isLoading"
                class="absolute inset-0 z-10 rounded-xl bg-background/80 backdrop-blur-[1px]"
            >
                <OperatorPageLoading class="p-1" />
            </div>
        </div>
    </div>
</template>
