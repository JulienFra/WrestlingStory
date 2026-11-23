<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Settings2 } from '@lucide/vue';
import CostCards from '@/components/admin/CostCards.vue';
import DailyCostTable from '@/components/admin/DailyCostTable.vue';
import RecentCallsTable from '@/components/admin/RecentCallsTable.vue';
import UserCostTable from '@/components/admin/UserCostTable.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/admin';
import { edit as settingsEdit } from '@/routes/admin/settings';
import type {
    CostTotals,
    DayCost,
    Option,
    RecentCall,
    UserCost,
} from '@/types/admin';

defineProps<{
    totals: CostTotals;
    perDay: DayCost[];
    perUser: UserCost[];
    recent: RecentCall[];
    provider: Option;
}>();

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
    <Head title="Coûts de l'IA" />

    <div class="flex h-full min-w-0 flex-1 flex-col gap-6 p-4">
        <header
            class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
        >
            <div class="space-y-1">
                <h1 class="text-xl font-semibold tracking-tight">
                    Coûts de l'IA
                </h1>
                <p class="text-sm text-muted-foreground">
                    Chaque appel au modèle, réussi, échoué ou refusé par le
                    quota. Les montants sont en dollars.
                </p>
                <p class="flex flex-wrap items-center gap-2 text-sm">
                    Fournisseur actif :
                    <Badge variant="outline" data-test="current-provider">
                        {{ provider.label }}
                    </Badge>
                </p>
            </div>
            <Button as-child variant="outline" class="w-full sm:w-auto">
                <Link :href="settingsEdit()">
                    <Settings2 />
                    Réglages IA
                </Link>
            </Button>
        </header>

        <div
            v-if="recent.length === 0"
            class="rounded-xl border border-dashed p-8 text-center"
        >
            <p class="font-medium">Aucun appel à l'IA pour l'instant.</p>
            <p class="mt-1 text-sm text-muted-foreground">
                Les chiffres apparaîtront ici dès la première génération, par
                exemple depuis l'atelier promo.
            </p>
        </div>

        <template v-else>
            <CostCards :totals="totals" />
            <div class="grid min-w-0 gap-6 xl:grid-cols-2">
                <DailyCostTable :days="perDay" />
                <UserCostTable v-if="perUser.length > 0" :users="perUser" />
            </div>
            <RecentCallsTable :calls="recent" />
        </template>
    </div>
</template>
