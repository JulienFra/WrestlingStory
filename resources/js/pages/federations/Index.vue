<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Lock, Plus, Users } from '@lucide/vue';
import { computed } from 'vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { create, index, show } from '@/routes/federations';
import type { FederationCard } from '@/types/federation';

const props = defineProps<{
    federations: FederationCard[];
    count: number;
    limit: number;
    tier: string;
    canCreate: boolean;
    limitMessage: string | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Mes fédérations',
                href: index(),
            },
        ],
    },
});

const tierLabel = computed(() => (props.tier === 'pro' ? 'Pro' : 'Gratuit'));
const unit = computed(() => (props.limit > 1 ? 'fédérations' : 'fédération'));
</script>

<template>
    <Head title="Mes fédérations" />

    <div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4">
        <header
            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="space-y-1">
                <h1 class="text-xl font-semibold tracking-tight">
                    Mes fédérations
                </h1>
                <p
                    class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground"
                    data-test="federation-count"
                >
                    {{ count }} / {{ limit }} {{ unit }}
                    <Badge variant="secondary">Palier {{ tierLabel }}</Badge>
                </p>
            </div>
            <Button
                v-if="canCreate && federations.length > 0"
                as-child
                class="w-full sm:w-auto"
            >
                <Link :href="create()" data-test="create-federation-button">
                    <Plus />
                    Créer une fédération
                </Link>
            </Button>
        </header>

        <Alert v-if="!canCreate && limitMessage" data-test="limit-message">
            <Lock />
            <AlertDescription>{{ limitMessage }}</AlertDescription>
        </Alert>

        <div
            v-if="federations.length === 0"
            class="flex flex-col items-center gap-4 rounded-lg border border-dashed p-8 text-center"
        >
            <p class="text-sm text-muted-foreground">
                Tu n'as pas encore de fédération. Crée la première : tu pourras
                ensuite y engager tes catcheurs, créer tes titres et booker tes
                main events.
            </p>
            <Button v-if="canCreate" as-child class="w-full sm:w-auto">
                <Link :href="create()" data-test="create-federation-button">
                    <Plus />
                    Créer ma première fédération
                </Link>
            </Button>
        </div>

        <ul v-else class="grid gap-4 sm:grid-cols-2">
            <li v-for="federation in federations" :key="federation.id">
                <Link
                    :href="show(federation.id)"
                    class="block h-full rounded-xl focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                >
                    <Card class="h-full transition-colors hover:bg-accent/50">
                        <CardHeader>
                            <CardTitle class="break-words">
                                {{ federation.name }}
                            </CardTitle>
                            <CardDescription
                                v-if="federation.description"
                                class="line-clamp-2 break-words"
                            >
                                {{ federation.description }}
                            </CardDescription>
                            <p
                                class="flex items-center gap-1 text-sm text-muted-foreground"
                            >
                                <Users class="size-4" />
                                {{ federation.wrestlersCount }} catcheur(s)
                            </p>
                        </CardHeader>
                    </Card>
                </Link>
            </li>
        </ul>
    </div>
</template>
