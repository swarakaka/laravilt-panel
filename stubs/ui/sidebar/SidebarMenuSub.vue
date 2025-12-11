<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <ul
    data-slot="sidebar-menu-sub"
    data-sidebar="menu-badge"
    :class="cn(
      // Use logical properties: ms-3.5 (margin-start) and border-s (border-start) for RTL
      'border-sidebar-border ms-3.5 me-3.5 flex min-w-0 ltr:translate-x-px rtl:-translate-x-px flex-col gap-1 border-s ps-2.5 pe-2.5 py-0.5',
      'group-data-[collapsible=icon]:hidden',
      'sidebar-menu-sub',
      props.class,
    )"
  >
    <slot />
  </ul>
</template>
