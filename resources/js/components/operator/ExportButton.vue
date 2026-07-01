<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import StatusChip from '@/components/operator/StatusChip.vue';

type ExportState = 'idle' | 'preparing' | 'complete' | 'error';

const props = withDefaults(
    defineProps<{
        label: string;
        action: string;
        params?: Record<string, string>;
        tone?: 'primary' | 'secondary';
    }>(),
    {
        params: () => ({}),
        tone: 'secondary',
    },
);
const emit = defineEmits<{
    downloadComplete: [payload: { filename: string; action: string; fileType: string; params: Record<string, string> }];
}>();

const labelByState = {
    idle: 'Ready',
    preparing: 'Preparing',
    complete: 'Complete',
    error: 'Error',
} as const;

const toneByState = {
    idle: 'neutral',
    preparing: 'warning',
    complete: 'success',
    error: 'danger',
} as const;

const state = ref<ExportState>('idle');
const detail = ref('Download will preserve the legacy filename and workbook response.');

const buttonClass = computed(() =>
    props.tone === 'primary'
        ? 'inline-flex items-center rounded-md bg-emerald-700 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-800 disabled:cursor-not-allowed disabled:opacity-60'
        : 'inline-flex items-center rounded-md border px-4 py-2 text-sm font-medium text-foreground transition hover:bg-muted disabled:cursor-not-allowed disabled:opacity-60',
);

const stateLabel = computed(() => labelByState[state.value]);

const stateTone = computed(() => toneByState[state.value]);

watch(
    () => props.params,
    () => {
        state.value = 'idle';
        detail.value = 'Download will preserve the legacy filename and workbook response.';
    },
    { deep: true },
);

const buildUrl = (): string => {
    const url = new URL(props.action, window.location.origin);

    Object.entries(props.params).forEach(([key, value]) => {
        const trimmed = value.trim();

        if (trimmed !== '') {
            url.searchParams.set(key, trimmed);
        }
    });

    return url.toString();
};

const responseFilename = (response: Response): string => {
    const disposition = response.headers.get('content-disposition') ?? '';

    const utfMatch = disposition.match(/filename\*=utf-8''([^;]+)/i);

    if (utfMatch?.[1] !== undefined) {
        return decodeURIComponent(utfMatch[1]).replace(/\+/g, ' ');
    }

    const basicMatch = disposition.match(/filename="?([^;"]+)"?/i);

    if (basicMatch?.[1] !== undefined) {
        return basicMatch[1];
    }

    return `${props.label.replace(/\s+/g, '_')}.xlsx`;
};

const legacyError = async (response: Response): Promise<string> => {
    const contentType = response.headers.get('content-type') ?? '';

    if (contentType.includes('application/json')) {
        const payload = (await response.json()) as {
            error?: string;
            errors?: Record<string, string[]>;
        };

        const validationMessage = Object.values(payload.errors ?? {})[0]?.[0];

        return validationMessage ?? payload.error ?? 'Legacy export request failed.';
    }

    const text = await response.text();

    return text.trim() !== '' ? text.trim() : 'Legacy export request failed.';
};

const download = async (): Promise<void> => {
    state.value = 'preparing';
    detail.value = 'Preparing legacy workbook download.';

    try {
        const response = await fetch(buildUrl(), {
            method: 'GET',
            credentials: 'same-origin',
        });

        if (! response.ok) {
            throw new Error(await legacyError(response));
        }

        const contentType = response.headers.get('content-type') ?? '';

        if (contentType.includes('application/json')) {
            throw new Error(await legacyError(response));
        }

        const blob = await response.blob();
        const filename = responseFilename(response);
        const objectUrl = window.URL.createObjectURL(blob);
        const anchor = document.createElement('a');

        anchor.href = objectUrl;
        anchor.download = filename;
        anchor.click();
        window.URL.revokeObjectURL(objectUrl);

        state.value = 'complete';
        detail.value = `Downloaded ${filename}.`;
        emit('downloadComplete', {
            filename,
            action: props.action,
            fileType: blob.type,
            params: { ...props.params },
        });
    } catch (error) {
        state.value = 'error';
        detail.value = error instanceof Error ? error.message : 'Legacy export request failed.';
    }
};
</script>

<template>
    <div class="space-y-2">
        <button :class="buttonClass" :disabled="state === 'preparing'" type="button" @click="download">
            {{ state === 'preparing' ? 'Preparing export...' : props.label }}
        </button>

        <div class="flex flex-wrap items-center gap-2">
            <StatusChip :label="stateLabel" :tone="stateTone" />
            <p class="text-xs leading-5 text-muted-foreground">
                {{ detail }}
            </p>
        </div>
    </div>
</template>
