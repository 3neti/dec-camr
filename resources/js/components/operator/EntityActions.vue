<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { RouteDefinition, RouteFormDefinition } from '@/wayfinder';

type EntityAction = {
    id: string;
    label: string;
    tone?: 'neutral' | 'danger';
    href?: string | RouteDefinition<'get'>;
    form?: RouteFormDefinition<'get' | 'post'>;
    fields?: Record<string, string | number>;
    target?: '_self' | '_blank';
    confirm?: string;
};

const props = withDefaults(
    defineProps<{
        orientation?: 'row' | 'column';
        actions?: EntityAction[];
        csrfToken?: string;
        groupLabel?: string;
    }>(),
    {
        orientation: 'row',
        actions: () => [],
        csrfToken: '',
        groupLabel: 'Record actions',
    },
);

const buttonClass = (tone: EntityAction['tone'] = 'neutral'): string =>
    tone === 'danger'
        ? 'inline-flex items-center rounded-md border border-red-200 px-3 py-2 text-sm font-medium text-red-700 transition hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 dark:border-red-900/80 dark:text-red-200 dark:hover:bg-red-950/60'
        : 'inline-flex items-center rounded-md border px-3 py-2 text-sm font-medium text-foreground transition hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2';

const shouldSubmit = (message?: string): boolean => {
    if (! message) {
        return true;
    }

    return window.confirm(message);
};
</script>

<template>
    <div
        :class="
            props.orientation === 'column'
                ? 'flex flex-col items-start gap-2'
                : 'flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end'
        "
        role="group"
        :aria-label="props.groupLabel"
    >
        <template v-if="props.actions.length > 0">
            <template v-for="action in props.actions" :key="action.id">
                <Link
                    v-if="action.href"
                    :href="action.href"
                    :target="action.target"
                    :class="buttonClass(action.tone)"
                >
                    {{ action.label }}
                </Link>

                <form
                    v-else-if="action.form"
                    :action="action.form.action"
                    :method="action.form.method === 'get' ? 'GET' : 'POST'"
                    :target="action.target"
                    class="inline"
                    @submit="shouldSubmit(action.confirm) || $event.preventDefault()"
                >
                    <input
                        v-if="action.form.method !== 'get' && props.csrfToken"
                        type="hidden"
                        name="_token"
                        :value="props.csrfToken"
                    />
                    <input
                        v-for="(value, key) in action.fields ?? {}"
                        :key="`${action.id}-${key}`"
                        type="hidden"
                        :name="key"
                        :value="String(value)"
                    />
                    <button :class="buttonClass(action.tone)" type="submit">
                        {{ action.label }}
                    </button>
                </form>
            </template>
        </template>

        <slot v-else />
    </div>
</template>
