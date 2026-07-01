<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { getOperatorShellNavigationSections } from '@/config/operatorShellNavigation';
import type { OperatorShellNavSection } from '@/config/operatorShellNavigation';
import { site } from '@/routes';

const page = usePage();

const userType = computed(
    () =>
        (
            page.props.auth as { user?: { user_type?: string | null } } | undefined
        )?.user?.user_type ?? null,
);

const operatorNavSections = computed(() => {
    return getOperatorShellNavigationSections(userType.value) as OperatorShellNavSection[];
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="site()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain
                v-for="section in operatorNavSections"
                :key="section.title"
                :title="section.title"
                :items="section.items"
            />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
