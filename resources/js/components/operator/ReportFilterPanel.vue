<script setup lang="ts">
import { computed, reactive } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type FilterOption = {
    label: string;
    value: string | number;
};

type FilterField = {
    id: string;
    name: string;
    label: string;
    type?: 'text' | 'number' | 'date' | 'time' | 'select';
    placeholder?: string;
    value?: string | number | null;
    options?: FilterOption[];
};

type FilterSection = {
    id: string;
    title: string;
    description?: string;
    fields: FilterField[];
};

type FilterAction = {
    id: string;
    label: string;
    method: 'get' | 'post';
    action: string;
    tone?: 'primary' | 'secondary';
};

const props = defineProps<{
    title: string;
    description?: string;
    sections: FilterSection[];
    actions: FilterAction[];
    csrfToken?: string;
}>();

const allFields = computed(() => props.sections.flatMap((section) => section.fields));

const formState = reactive<Record<string, string>>(
    Object.fromEntries(
        allFields.value.map((field) => [field.name, field.value === null || field.value === undefined ? '' : String(field.value)]),
    ),
);

const buttonClass = (tone?: 'primary' | 'secondary') =>
    tone === 'secondary'
        ? 'inline-flex items-center rounded-md border px-4 py-2 text-sm font-medium text-foreground transition hover:bg-muted'
        : 'inline-flex items-center rounded-md bg-emerald-700 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-800';
</script>

<template>
    <Card class="py-5">
        <CardHeader class="px-5 pb-0">
            <CardTitle>{{ props.title }}</CardTitle>
            <CardDescription v-if="props.description">{{ props.description }}</CardDescription>
        </CardHeader>

        <CardContent class="space-y-6 px-5">
            <section v-for="section in props.sections" :key="section.id" class="space-y-3">
                <div class="space-y-1">
                    <h3 class="text-sm font-semibold text-foreground">{{ section.title }}</h3>
                    <p v-if="section.description" class="text-sm text-muted-foreground">{{ section.description }}</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div v-for="field in section.fields" :key="field.id" class="space-y-2">
                        <Label :for="field.id">{{ field.label }}</Label>

                        <select
                            v-if="field.type === 'select'"
                            :id="field.id"
                            v-model="formState[field.name]"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                        >
                            <option value="">Select</option>
                            <option v-for="option in field.options ?? []" :key="`${field.id}-${option.value}`" :value="String(option.value)">
                                {{ option.label }}
                            </option>
                        </select>

                        <Input
                            v-else
                            :id="field.id"
                            v-model="formState[field.name]"
                            :type="field.type ?? 'text'"
                            :name="field.name"
                            :placeholder="field.placeholder"
                        />
                    </div>
                </div>
            </section>

            <div class="flex flex-wrap gap-3">
                <form
                    v-for="action in props.actions"
                    :key="action.id"
                    :action="action.action"
                    :method="action.method === 'get' ? 'GET' : 'POST'"
                    class="inline"
                >
                    <input v-if="action.method === 'post' && props.csrfToken" type="hidden" name="_token" :value="props.csrfToken" />
                    <input
                        v-for="field in allFields"
                        :key="`${action.id}-${field.name}`"
                        type="hidden"
                        :name="field.name"
                        :value="formState[field.name] ?? ''"
                    />
                    <button :class="buttonClass(action.tone)" type="submit">
                        {{ action.label }}
                    </button>
                </form>
            </div>
        </CardContent>
    </Card>
</template>
