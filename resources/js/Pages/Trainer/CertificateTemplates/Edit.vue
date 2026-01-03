<script setup>
import { Head } from "@inertiajs/vue3";
import TrainerLayout from "@/Layouts/TrainerLayout.vue";
import TemplateForm from "@/Pages/Admin/CertificateTemplates/Partials/TemplateForm.vue";

const props = defineProps({
    templateId: {
        type: [String, Number],
        required: true,
    },
});
</script>

<template>
    <Head title="Edit Certificate Template" />
    <TrainerLayout>
        <TemplateForm
            :template-id="props.templateId"
            api-base="/api/trainer/certificate-templates"
            index-path="/trainer/certificate-templates"
            programs-endpoint="/api/trainer/sessions"
        />
    </TrainerLayout>
</template>
