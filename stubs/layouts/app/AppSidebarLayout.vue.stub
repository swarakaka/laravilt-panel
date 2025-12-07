<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import { NotificationContainer, useNotification } from '@laravilt/notifications/app.ts';
import { usePage } from '@inertiajs/vue3';
import { onMounted, watch } from 'vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const { notify: showNotification } = useNotification();

// Display session notifications on mount and when page props change
const displaySessionNotifications = () => {
    const notifications = page.props.notifications as Array<any>;
    if (notifications && Array.isArray(notifications)) {
        notifications.forEach((notification) => {
            // Support both old format (message) and new format (title/body)
            if (notification.title || notification.body) {
                showNotification(notification.title || '', notification.body, notification.type, {
                    icon: notification.icon,
                    color: notification.color,
                    actions: notification.actions,
                    duration: notification.duration,
                });
            } else {
                // Backward compatibility: just message
                showNotification(notification.message, undefined, notification.type);
            }
        });
    }
};

onMounted(() => {
    displaySessionNotifications();
});

// Watch for changes to notifications in page props (for Inertia visits)
watch(() => page.props.notifications, (newNotifications) => {
    if (newNotifications && Array.isArray(newNotifications) && newNotifications.length > 0) {
        displaySessionNotifications();
    }
}, { deep: true });
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
        <NotificationContainer />
    </AppShell>
</template>
