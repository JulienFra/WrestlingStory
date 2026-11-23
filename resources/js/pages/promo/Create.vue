<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { CircleAlert, Sparkles } from '@lucide/vue';
import { ref } from 'vue';
import PromoController from '@/actions/App/Http/Controllers/PromoController';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { create } from '@/routes/promo';
import type { PromoResult } from '@/types/promo';

const props = defineProps<{
    remainingToday: number;
    dailyLimit: number;
    result: PromoResult | null;
    oldIdea: string | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Atelier promo',
                href: create(),
            },
        ],
    },
});

const maxLength = 500;
const idea = ref(props.oldIdea ?? '');
</script>

<template>
    <Head title="Atelier promo" />

    <div class="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4">
        <header class="space-y-1">
            <h1 class="text-xl font-semibold tracking-tight">Atelier promo</h1>
            <p class="text-sm text-muted-foreground">
                Décris un catcheur imaginaire : son allure, son caractère, son
                rival. L'IA écrit le discours qu'il lance face caméra.
            </p>
        </header>

        <Alert v-if="$page.props.errors.ai" variant="destructive">
            <CircleAlert />
            <AlertTitle>La promo n'a pas pu être générée</AlertTitle>
            <AlertDescription>{{ $page.props.errors.ai }}</AlertDescription>
        </Alert>

        <Form
            v-bind="PromoController.store.form()"
            class="space-y-4"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="idea">Ton catcheur</Label>
                <textarea
                    id="idea"
                    v-model="idea"
                    name="idea"
                    rows="5"
                    required
                    minlength="10"
                    :maxlength="maxLength"
                    placeholder="Ex. : un bûcheron géant qui parle aux arbres et veut venger sa forêt."
                    aria-describedby="idea-help"
                    class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm dark:bg-input/30"
                />
                <p
                    id="idea-help"
                    class="flex justify-between gap-2 text-sm text-muted-foreground"
                >
                    <span>De 10 à {{ maxLength }} caractères.</span>
                    <span>{{ idea.length }} / {{ maxLength }}</span>
                </p>
                <InputError :message="errors.idea" />
            </div>

            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <p class="text-sm text-muted-foreground" data-test="quota">
                    Il te reste {{ remainingToday }} génération(s) aujourd'hui
                    sur {{ dailyLimit }}.
                </p>
                <Button
                    type="submit"
                    :disabled="processing"
                    class="w-full sm:w-auto"
                    data-test="generate-promo-button"
                >
                    <Spinner v-if="processing" />
                    Générer la promo
                </Button>
            </div>
        </Form>

        <Card v-if="result">
            <CardHeader>
                <div class="flex flex-wrap items-center gap-2">
                    <CardTitle>La promo</CardTitle>
                    <Badge v-if="result.aiGenerated" variant="secondary">
                        <Sparkles />
                        Généré par IA
                    </Badge>
                </div>
                <CardDescription>Modèle : {{ result.model }}</CardDescription>
            </CardHeader>
            <CardContent>
                <p class="leading-relaxed whitespace-pre-line">
                    {{ result.text }}
                </p>
            </CardContent>
        </Card>
    </div>
</template>
