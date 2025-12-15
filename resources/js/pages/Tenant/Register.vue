<script setup lang="ts">
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Building2 } from 'lucide-vue-next';
import { useLocalization } from '@/composables/useLocalization';
import InputError from '@/components/InputError.vue';

const { trans } = useLocalization();

const props = defineProps<{
    panel: {
        id: string;
        path: string;
    };
}>();

const form = useForm({
    name: '',
    slug: '',
});

const submit = () => {
    form.post(`/${props.panel.path}/tenant/register`);
};
</script>

<template>
    <Head :title="trans('panel::panel.tenancy.create_tenant')" />

    <div class="min-h-screen flex items-center justify-center bg-background p-4">
        <Card class="w-full max-w-md">
            <CardHeader class="text-center">
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-primary/10">
                    <Building2 class="h-6 w-6 text-primary" />
                </div>
                <CardTitle class="text-2xl">{{ trans('panel::panel.tenancy.create_tenant') }}</CardTitle>
                <CardDescription>
                    {{ trans('panel::panel.tenancy.create_tenant_description') }}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="name">{{ trans('panel::panel.tenancy.team_name') }}</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            type="text"
                            :placeholder="trans('panel::panel.tenancy.team_name_placeholder')"
                            required
                            autofocus
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="space-y-2">
                        <Label for="slug">{{ trans('panel::panel.tenancy.team_slug') }}</Label>
                        <Input
                            id="slug"
                            v-model="form.slug"
                            type="text"
                            :placeholder="trans('panel::panel.tenancy.team_slug_placeholder')"
                        />
                        <p class="text-xs text-muted-foreground">
                            {{ trans('panel::panel.tenancy.team_slug_help') }}
                        </p>
                        <InputError :message="form.errors.slug" />
                    </div>

                    <Button type="submit" class="w-full" :disabled="form.processing">
                        <span v-if="form.processing">{{ trans('panel::panel.common.loading') }}</span>
                        <span v-else>{{ trans('panel::panel.tenancy.create_tenant') }}</span>
                    </Button>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
