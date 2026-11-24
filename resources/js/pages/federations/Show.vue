<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import FederationController from '@/actions/App/Http/Controllers/FederationController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { index } from '@/routes/federations';
import type { FederationDetail } from '@/types/federation';

const props = defineProps<{
    federation: FederationDetail;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Mes fédérations', href: index() }],
    },
});

const counters = computed(() => [
    { label: 'Catcheurs', value: props.federation.wrestlersCount },
    { label: 'Titres', value: props.federation.titlesCount },
    {
        label: 'Rivalités en cours',
        value: props.federation.activeRivalriesCount,
    },
    {
        label: 'Main events prévus',
        value: props.federation.scheduledMainEventsCount,
    },
]);
</script>

<template>
    <Head :title="federation.name" />

    <div class="mx-auto flex w-full max-w-4xl flex-col gap-8 p-4">
        <header class="space-y-1">
            <h1 class="text-xl font-semibold tracking-tight break-words">
                {{ federation.name }}
            </h1>
            <p
                v-if="federation.description"
                class="text-sm break-words whitespace-pre-line text-muted-foreground"
            >
                {{ federation.description }}
            </p>
        </header>

        <dl class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div
                v-for="counter in counters"
                :key="counter.label"
                class="rounded-xl border p-4"
            >
                <dt class="text-sm text-muted-foreground">
                    {{ counter.label }}
                </dt>
                <dd class="text-2xl font-semibold">{{ counter.value }}</dd>
            </div>
        </dl>

        <section
            class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10"
        >
            <div class="space-y-0.5 text-red-600 dark:text-red-100">
                <h2 class="font-medium">Zone dangereuse</h2>
                <p class="text-sm">
                    Supprimer la fédération efface tout son contenu. Cette
                    action est définitive.
                </p>
            </div>
            <Dialog>
                <DialogTrigger as-child>
                    <Button
                        variant="destructive"
                        class="w-full sm:w-auto"
                        data-test="delete-federation-button"
                    >
                        Supprimer la fédération
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <Form
                        v-bind="
                            FederationController.destroy.form(federation.id)
                        "
                        class="space-y-6"
                        v-slot="{ processing }"
                    >
                        <DialogHeader class="space-y-3">
                            <DialogTitle class="break-words">
                                Supprimer la fédération « {{ federation.name }}
                                » ?
                            </DialogTitle>
                            <DialogDescription>
                                Ses catcheurs, titres, rivalités et main events
                                seront supprimés. Cette action est définitive.
                            </DialogDescription>
                        </DialogHeader>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button variant="secondary">Annuler</Button>
                            </DialogClose>
                            <Button
                                type="submit"
                                variant="destructive"
                                :disabled="processing"
                                data-test="confirm-delete-federation-button"
                            >
                                <Spinner v-if="processing" />
                                Supprimer définitivement
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </section>
    </div>
</template>
