<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@laravilt/panel/components/PanelSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import { NotificationContainer } from '@laravilt/notifications/app.ts';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface BreadcrumbItem {
    title: string;
    href: string;
}

interface Props {
    breadcrumbs?: BreadcrumbItem[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

// Get shared panel data from Inertia
const page = usePage();
const panelData = computed(() => page.props.panel as any);
const user = computed(() => page.props.auth?.user as any);
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar
            :navigation="panelData?.navigation"
            :panel="panelData"
            :user="user"
        />
        <AppContent variant="sidebar" class="overflow-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
        <NotificationContainer />
    </AppShell>
</template>
