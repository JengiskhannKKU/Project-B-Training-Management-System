<script setup>
import { Head } from "@inertiajs/vue3";
import TrainerLayout from "@/Layouts/TrainerLayout.vue";
import TemplateForm from "@/Pages/Admin/CertificateTemplates/Partials/TemplateForm.vue";
</script>

<template>
    <Head title="Create Certificate Template" />
    <TrainerLayout>
        <TemplateForm
            api-base="/api/trainer/certificate-templates"
            index-path="/trainer/certificate-templates"
            programs-endpoint="/api/trainer/sessions"
        />
    </TrainerLayout>
</template>
