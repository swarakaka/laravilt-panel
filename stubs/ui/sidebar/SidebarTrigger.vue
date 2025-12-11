<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { PanelLeft, PanelRight } from 'lucide-vue-next'
import { useSidebar } from './utils'
import { useLocalization } from '@/composables/useLocalization'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()

const { toggleSidebar } = useSidebar()
const { isRtl, trans } = useLocalization()
</script>

<template>
  <Button
    data-sidebar="trigger"
    data-slot="sidebar-trigger"
    variant="ghost"
    size="icon"
    :class="cn('h-7 w-7', props.class)"
    @click="toggleSidebar"
  >
    <PanelRight v-if="isRtl" />
    <PanelLeft v-else />
    <span class="sr-only">{{ trans('sidebar.toggle') }}</span>
  </Button>
</template>
