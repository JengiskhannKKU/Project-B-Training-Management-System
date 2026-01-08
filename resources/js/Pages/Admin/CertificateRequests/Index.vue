<script setup>
import { Head } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import CertificateRequestsList from "@/Components/Shared/CertificateRequestsList.vue";
</script>

<template>
    <Head title="Certificate Requests" />
    <AdminLayout>
        <CertificateRequestsList
            apiEndpoint="/api/admin/certificate-requests"
            detailsRoute="admin.certificate-requests.show"
            :showTrainerColumn="true"
        />
    </AdminLayout>
</template>
