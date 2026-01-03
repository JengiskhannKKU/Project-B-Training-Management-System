<script setup>
import { Head } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import TemplateForm from "@/Pages/Admin/CertificateTemplates/Partials/TemplateForm.vue";
</script>

<template>
    <Head title="Create Certificate Template" />
    <AdminLayout>
        <TemplateForm />
    </AdminLayout>
</template>
