<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import type { RecentCall } from '@/types/admin';
import AdminTable from './AdminTable.vue';
import { formatCost, formatInteger } from './format';

defineProps<{
    calls: RecentCall[];
}>();
</script>

<template>
    <AdminTable title="Derniers appels" description="Les 20 plus récents.">
        <thead class="bg-muted/50 text-muted-foreground">
            <tr>
                <th class="px-3 py-2 font-medium">Date (UTC)</th>
                <th class="px-3 py-2 font-medium">Pseudo</th>
                <th class="px-3 py-2 font-medium">Fonction</th>
                <th class="px-3 py-2 font-medium">Fournisseur</th>
                <th class="px-3 py-2 font-medium">Modèle</th>
                <th class="px-3 py-2 text-right font-medium">
                    Jetons entrée / sortie
                </th>
                <th class="px-3 py-2 text-right font-medium">Coût</th>
                <th class="px-3 py-2 text-right font-medium">Durée</th>
                <th class="px-3 py-2 font-medium">Résultat</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="call in calls" :key="call.id" class="border-t">
                <td class="px-3 py-2 tabular-nums">{{ call.createdAt }}</td>
                <td class="px-3 py-2">{{ call.user }}</td>
                <td class="px-3 py-2">{{ call.feature }}</td>
                <td class="px-3 py-2">{{ call.provider }}</td>
                <td class="px-3 py-2">{{ call.model }}</td>
                <td class="px-3 py-2 text-right tabular-nums">
                    {{ formatInteger(call.inputTokens) }} /
                    {{ formatInteger(call.outputTokens) }}
                </td>
                <td class="px-3 py-2 text-right tabular-nums">
                    {{ formatCost(call.cost) }}
                </td>
                <td class="px-3 py-2 text-right tabular-nums">
                    {{ formatInteger(call.durationMs) }} ms
                </td>
                <td class="px-3 py-2">
                    <Badge v-if="call.success" variant="secondary">
                        Réussi
                    </Badge>
                    <Badge v-else variant="destructive">{{ call.error }}</Badge>
                </td>
            </tr>
        </tbody>
    </AdminTable>
</template>
