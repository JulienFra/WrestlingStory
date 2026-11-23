<script setup lang="ts">
import type { DayCost } from '@/types/admin';
import AdminTable from './AdminTable.vue';
import { formatCost, formatDate, formatInteger } from './format';

defineProps<{
    days: DayCost[];
}>();
</script>

<template>
    <AdminTable title="Par jour" description="Les 14 derniers jours (UTC).">
        <thead class="bg-muted/50 text-muted-foreground">
            <tr>
                <th class="px-3 py-2 font-medium">Date</th>
                <th class="px-3 py-2 text-right font-medium">Appels</th>
                <th class="px-3 py-2 text-right font-medium">Échecs</th>
                <th class="px-3 py-2 text-right font-medium">Coût</th>
            </tr>
        </thead>
        <tbody>
            <tr
                v-for="day in days"
                :key="day.date"
                class="border-t"
                :class="{ 'text-muted-foreground': day.calls === 0 }"
            >
                <td class="px-3 py-2">{{ formatDate(day.date) }}</td>
                <td class="px-3 py-2 text-right tabular-nums">
                    {{ formatInteger(day.calls) }}
                </td>
                <td class="px-3 py-2 text-right tabular-nums">
                    {{ formatInteger(day.failed) }}
                </td>
                <td class="px-3 py-2 text-right tabular-nums">
                    {{ formatCost(day.cost) }}
                </td>
            </tr>
        </tbody>
    </AdminTable>
</template>
