<script setup lang="ts">
// A titled HTML table that scrolls horizontally on a phone.
defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <section class="space-y-2">
        <header>
            <h2 class="text-base font-medium">{{ title }}</h2>
            <p v-if="description" class="text-sm text-muted-foreground">
                {{ description }}
            </p>
        </header>
        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <slot />
            </table>
        </div>
    </section>
</template>
