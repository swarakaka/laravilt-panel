<script setup lang="ts">
import type { TabsContentProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { TabsContent } from "reka-ui"
import { cn } from "@/lib/utils"

const props = defineProps<TabsContentProps & { class?: HTMLAttributes["class"] }>()

const delegatedProps = reactiveOmit(props, "class")
</script>

<template>
  <TabsContent
    v-cloak
    :class="cn('mt-4 ring-offset-background focus-visible:outline-none animate-in fade-in-0 duration-200', props.class)"
    v-bind="delegatedProps"
  >
    <slot />
  </TabsContent>
</template>
