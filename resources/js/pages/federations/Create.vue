<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import FederationController from '@/actions/App/Http/Controllers/FederationController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { create, index } from '@/routes/federations';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Mes fédérations', href: index() },
            { title: 'Nouvelle fédération', href: create() },
        ],
    },
});

const maxDescription = 500;
const description = ref('');
</script>

<template>
    <Head title="Nouvelle fédération" />

    <div class="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4">
        <header class="space-y-1">
            <h1 class="text-xl font-semibold tracking-tight">
                Nouvelle fédération
            </h1>
            <p class="text-sm text-muted-foreground">
                Donne-lui un nom et, si tu veux, quelques mots sur son style.
            </p>
        </header>

        <!-- Inertia's Form keeps what the player typed when the server returns errors. -->
        <Form
            v-bind="FederationController.store.form()"
            class="space-y-4"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Nom</Label>
                <Input
                    id="name"
                    name="name"
                    required
                    minlength="3"
                    maxlength="60"
                    autocomplete="off"
                    placeholder="Ex. : Fédération du Grand Nord"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="description">Description (facultative)</Label>
                <textarea
                    id="description"
                    v-model="description"
                    name="description"
                    rows="4"
                    :maxlength="maxDescription"
                    placeholder="Ex. : des combats rugueux dans des granges glacées."
                    aria-describedby="description-help"
                    class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm dark:bg-input/30"
                />
                <p
                    id="description-help"
                    class="text-right text-sm text-muted-foreground"
                >
                    {{ description.length }} / {{ maxDescription }}
                </p>
                <InputError :message="errors.description" />
            </div>

            <Button
                type="submit"
                :disabled="processing"
                class="w-full sm:w-auto"
                data-test="store-federation-button"
            >
                <Spinner v-if="processing" />
                Créer la fédération
            </Button>
        </Form>
    </div>
</template>
