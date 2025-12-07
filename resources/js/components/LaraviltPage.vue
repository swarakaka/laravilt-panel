<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import PanelLayout from '@laravilt/panel/layouts/PanelLayout.vue'

interface Props {
    page?: {
        heading?: string
        subheading?: string
        title?: string
    }
    breadcrumbs?: Array<{ title: string; href?: string }>
    panel?: {
        id: string
        path: string
        brandName: string
        brandLogo?: string
    }
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
})
</script>

<template>
    <Head :title="page?.title || page?.heading || 'Page'" />

    <PanelLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div v-if="page?.heading" class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">{{ page.heading }}</h1>
                    <p v-if="page.subheading" class="text-sm text-muted-foreground mt-1">
                        {{ page.subheading }}
                    </p>
                </div>
            </div>

            <!-- Page Content Slot -->
            <div class="flex-1">
                <slot />
            </div>
        </div>
    </PanelLayout>
</template>
