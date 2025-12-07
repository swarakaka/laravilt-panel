<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import PanelLayout from './PanelLayout.vue';

interface NavigationItem {
    title: string;
    href: string;
    icon?: string;
    active?: boolean;
}

interface BreadcrumbItem {
    title: string;
    href: string;
}

const props = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
    navigation?: NavigationItem[];
    title?: string;
    description?: string;
}>();

// Get current URL to determine active navigation item
const currentUrl = computed(() => {
    return window.location.pathname;
});

// Enhance navigation items with active state
const enhancedNavigation = computed(() => {
    if (!props.navigation) return [];

    return props.navigation.map(item => ({
        ...item,
        active: currentUrl.value === item.href,
    }));
});
</script>

<template>
    <PanelLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 py-6">
            <!-- Settings Title and Description -->
            <div v-if="title" class="mb-8 space-y-0.5">
                <h2 class="text-xl font-semibold tracking-tight">{{ title }}</h2>
                <p v-if="description" class="text-sm text-muted-foreground">
                    {{ description }}
                </p>
            </div>

            <div class="flex flex-col lg:flex-row lg:space-x-12">
                <!-- Sidebar Navigation -->
                <aside class="w-full max-w-xl lg:w-48">
                    <nav class="flex flex-col space-y-1 space-x-0">
                        <Link
                            v-for="item in enhancedNavigation"
                            :key="item.href"
                            :href="item.href"
                            data-slot="button"
                            :class="[
                                'inline-flex items-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all',
                                'disabled:pointer-events-none disabled:opacity-50',
                                '[&_svg]:pointer-events-none [&_svg:not([class*=\'size-\'])]:size-4 shrink-0 [&_svg]:shrink-0',
                                'outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
                                'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
                                'hover:bg-accent hover:text-accent-foreground dark:hover:bg-accent/50',
                                'h-9 px-4 py-2 has-[>svg]:px-3 w-full justify-start',
                                item.active ? 'bg-muted' : '',
                            ]"
                        >
                            {{ item.title }}
                        </Link>
                    </nav>
                </aside>

                <!-- Separator for mobile -->
                <div
                    data-orientation="horizontal"
                    role="none"
                    data-slot="separator-root"
                    class="bg-border shrink-0 data-[orientation=horizontal]:h-px data-[orientation=horizontal]:w-full data-[orientation=vertical]:h-full data-[orientation=vertical]:w-px my-6 lg:hidden"
                ></div>

                <!-- Main Content Area -->
                <div class="flex-1 md:max-w-2xl">
                    <slot />
                </div>
            </div>
        </div>
    </PanelLayout>
</template>
