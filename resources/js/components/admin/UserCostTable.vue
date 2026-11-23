<script setup lang="ts">
import type { UserCost } from '@/types/admin';
import AdminTable from './AdminTable.vue';
import { formatCost, formatInteger, tierLabel } from './format';

defineProps<{
    users: UserCost[];
}>();
</script>

<template>
    <AdminTable
        title="Par utilisateur"
        description="Les 20 utilisateurs qui ont coûté le plus sur 30 jours."
    >
        <thead class="bg-muted/50 text-muted-foreground">
            <tr>
                <th class="px-3 py-2 font-medium">Pseudo</th>
                <th class="px-3 py-2 font-medium">Palier</th>
                <th class="px-3 py-2 text-right font-medium">Appels</th>
                <th class="px-3 py-2 text-right font-medium">Échecs</th>
                <th class="px-3 py-2 text-right font-medium">Coût</th>
                <th class="px-3 py-2 text-right font-medium">
                    Aujourd'hui / quota
                </th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="user in users" :key="user.name" class="border-t">
                <td class="px-3 py-2 font-medium">{{ user.name }}</td>
                <td class="px-3 py-2">{{ tierLabel(user.tier) }}</td>
                <td class="px-3 py-2 text-right tabular-nums">
                    {{ formatInteger(user.calls) }}
                </td>
                <td class="px-3 py-2 text-right tabular-nums">
                    {{ formatInteger(user.failed) }}
                </td>
                <td class="px-3 py-2 text-right tabular-nums">
                    {{ formatCost(user.cost) }}
                </td>
                <td class="px-3 py-2 text-right tabular-nums">
                    {{ user.callsToday }}
                    <template v-if="user.dailyLimit !== null">
                        / {{ user.dailyLimit }}
                    </template>
                </td>
            </tr>
        </tbody>
    </AdminTable>
</template>
