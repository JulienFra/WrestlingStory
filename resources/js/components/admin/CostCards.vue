<script setup lang="ts">
import { computed } from 'vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { CostTotals } from '@/types/admin';
import { formatCost, formatInteger, formatPercent } from './format';

const props = defineProps<{
    totals: CostTotals;
}>();

const cards = computed(() => [
    {
        title: 'Coût sur 30 jours',
        value: formatCost(props.totals.cost),
        detail: `${formatInteger(props.totals.inputTokens)} jetons en entrée, ${formatInteger(props.totals.outputTokens)} en sortie`,
    },
    {
        title: 'Appels',
        value: formatInteger(props.totals.calls),
        detail: `${formatInteger(props.totals.succeeded)} réussis`,
    },
    {
        title: "Taux d'échec",
        value: formatPercent(props.totals.failureRate),
        detail: `${formatInteger(props.totals.failed)} échecs, hors refus de quota`,
    },
    {
        title: 'Refus de quota',
        value: formatInteger(props.totals.quotaRefused),
        detail: 'Appels bloqués avant le modèle',
    },
]);
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Card v-for="card in cards" :key="card.title" class="gap-2 py-4">
            <CardHeader class="px-4">
                <CardDescription>{{ card.title }}</CardDescription>
                <CardTitle class="text-2xl tabular-nums">
                    {{ card.value }}
                </CardTitle>
            </CardHeader>
            <CardContent class="px-4 text-xs text-muted-foreground">
                {{ card.detail }}
            </CardContent>
        </Card>
    </div>
</template>
