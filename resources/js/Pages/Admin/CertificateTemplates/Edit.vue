<script setup>
import { Head } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
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
    <AdminLayout>
        <TemplateForm :template-id="props.templateId" />
    </AdminLayout>
</template>
