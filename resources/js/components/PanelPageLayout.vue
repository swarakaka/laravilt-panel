<template>
    <!-- Slot mode for Blade usage -->
    <slot v-if="$slots.default" />

    <!-- Direct Vue mode -->
    <AppShell v-else variant="sidebar">
        <PanelSidebar
            :navigation="navigation"
            :panel="panel"
            :user="user"
        />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />

            <div class="flex h-full flex-1 flex-col gap-4 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold">{{ heading }}</h1>
                        <p v-if="subheading" class="text-sm text-muted-foreground mt-1">
                            {{ subheading }}
                        </p>
                    </div>

                    <!-- Header actions placeholder -->
                    <div v-if="headerActions && headerActions.length">
                        <!-- TODO: Render header actions when actions package is ready -->
                    </div>
                </div>

                <!-- Page Content Area -->
                <div class="flex-1">
                    <div v-if="content" v-html="content" />
                    <slot v-else name="content">
                        <div class="flex h-full items-center justify-center rounded-lg border border-dashed p-8">
                            <div class="text-center">
                                <h3 class="text-lg font-semibold">Custom Page Content</h3>
                                <p class="text-sm text-muted-foreground mt-2">
                                    This is a standalone page. You can add custom components and content here.
                                </p>
                            </div>
                        </div>
                    </slot>
                </div>
            </div>
        </AppContent>
    </AppShell>
</template>

<script setup lang="ts">
import { useSlots } from 'vue'
import AppShell from '@/components/AppShell.vue'
import AppContent from '@/components/AppContent.vue'
import AppSidebarHeader from '@/components/AppSidebarHeader.vue'
import PanelSidebar from './PanelSidebar.vue'

const slots = useSlots()

interface BreadcrumbItem {
    title: string
    href: string
}

const props = defineProps<{
    breadcrumbs?: BreadcrumbItem[]
    heading?: string
    subheading?: string | null
    headerActions?: any[]
    content?: string | null
    navigation?: any[]
    panel?: {
        id: string
        path: string
        brandName: string
        brandLogo?: string
    }
    user?: {
        name: string
        email: string
    }
}>()
</script>
