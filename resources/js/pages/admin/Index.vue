<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { index } from '@/routes/admin';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Administration',
                href: index(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Administration" />

    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <h1 class="text-xl font-semibold">Administration</h1>
        <p class="text-muted-foreground">
            Le tableau de bord des coûts de l'IA et des quotas par palier arrive
            au prochain lot.
        </p>
    </div>
</template>
