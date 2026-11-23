<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import SettingsController from '@/actions/App/Http/Controllers/Admin/SettingsController';
import AdminTable from '@/components/admin/AdminTable.vue';
import { tierLabel } from '@/components/admin/format';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/admin';
import { edit } from '@/routes/admin/settings';
import type { AdminUserRow, Option } from '@/types/admin';

defineProps<{
    provider: string;
    providers: Option[];
    tiers: Option[];
    users: AdminUserRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Administration', href: index() },
            { title: 'Réglages IA', href: edit() },
        ],
    },
});

const selectClass =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm dark:bg-input/30';
</script>

<template>
    <Head title="Réglages IA" />

    <div class="mx-auto flex w-full max-w-4xl min-w-0 flex-col gap-8 p-4">
        <h1 class="text-xl font-semibold tracking-tight">Réglages IA</h1>

        <section class="space-y-3">
            <header class="space-y-1">
                <h2 class="text-base font-medium">Fournisseur d'IA</h2>
                <p class="text-sm text-muted-foreground">
                    « OpenRouter (réel) » appelle le vrai modèle et coûte de
                    l'argent. Les choix « Faux : … » n'appellent aucun réseau :
                    ils servent à montrer une réponse de démonstration ou
                    chacune des quatre erreurs d'IA.
                </p>
            </header>

            <Form
                v-bind="SettingsController.updateAi.form()"
                class="flex flex-col gap-3 sm:flex-row sm:items-end"
                v-slot="{ errors, processing }"
            >
                <div class="grid flex-1 gap-2">
                    <Label for="provider">Fournisseur</Label>
                    <select id="provider" name="provider" :class="selectClass">
                        <option
                            v-for="option in providers"
                            :key="option.value"
                            :value="option.value"
                            :selected="option.value === provider"
                        >
                            {{ option.label }}
                        </option>
                    </select>
                    <InputError :message="errors.provider" />
                </div>
                <Button
                    type="submit"
                    :disabled="processing"
                    data-test="save-provider-button"
                >
                    Enregistrer
                </Button>
            </Form>
        </section>

        <AdminTable
            title="Paliers des utilisateurs"
            description="Pas de paiement : c'est l'administrateur qui change le palier."
        >
            <thead class="bg-muted/50 text-muted-foreground">
                <tr>
                    <th class="px-3 py-2 font-medium">Pseudo</th>
                    <th class="px-3 py-2 font-medium">E-mail</th>
                    <th class="px-3 py-2 font-medium">Rôle</th>
                    <th class="px-3 py-2 text-right font-medium">
                        Appels du jour
                    </th>
                    <th class="px-3 py-2 font-medium">Palier</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="user in users" :key="user.id" class="border-t">
                    <td class="px-3 py-2 font-medium">{{ user.name }}</td>
                    <td class="px-3 py-2">{{ user.email }}</td>
                    <td class="px-3 py-2">
                        {{ user.role === 'admin' ? 'Admin' : 'Joueur' }}
                    </td>
                    <td class="px-3 py-2 text-right tabular-nums">
                        {{ user.callsToday }} / {{ user.dailyLimit }}
                    </td>
                    <td class="px-3 py-2">
                        <Form
                            v-bind="SettingsController.updateTier.form(user.id)"
                            class="flex items-center gap-2"
                            v-slot="{ errors, processing }"
                        >
                            <label :for="`tier-${user.id}`" class="sr-only">
                                Palier de {{ user.name }} (actuel :
                                {{ tierLabel(user.tier) }})
                            </label>
                            <select
                                :id="`tier-${user.id}`"
                                name="tier"
                                :class="[selectClass, 'min-w-44']"
                            >
                                <option
                                    v-for="tier in tiers"
                                    :key="tier.value"
                                    :value="tier.value"
                                    :selected="tier.value === user.tier"
                                >
                                    {{ tier.label }}
                                </option>
                            </select>
                            <Button
                                type="submit"
                                size="sm"
                                :disabled="processing"
                            >
                                Enregistrer
                            </Button>
                            <InputError :message="errors.tier" />
                        </Form>
                    </td>
                </tr>
            </tbody>
        </AdminTable>
    </div>
</template>
