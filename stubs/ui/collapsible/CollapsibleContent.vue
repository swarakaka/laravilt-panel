<script setup lang="ts">
import { CollapsibleContent, type CollapsibleContentProps } from 'reka-ui'

const props = defineProps<CollapsibleContentProps>()
</script>

<template>
  <CollapsibleContent
    data-slot="collapsible-content"
    class="overflow-hidden"
    v-bind="props"
  >
    <slot />
  </CollapsibleContent>
</template>
