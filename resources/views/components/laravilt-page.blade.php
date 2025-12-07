@props(['page'])

@php
    $props = $page->toLaraviltProps();
@endphp

<x-laravilt-vue-component
    component="LaraviltPage"
    :props="$props"
>
    {{ $slot }}
</x-laravilt-vue-component>
