<script setup>
import { Head } from "@inertiajs/vue3";
import TrainerLayout from "@/Layouts/TrainerLayout.vue";
import CertificateRequestsList from "@/Components/Shared/CertificateRequestsList.vue";
</script>

<template>
    <Head title="Certificate Requests" />
    <TrainerLayout>
        <CertificateRequestsList
            apiEndpoint="/api/trainer/certificate-requests"
            detailsRoute="trainer.certificate-requests.show"
            :showTrainerColumn="false"
        />
    </TrainerLayout>
</template>
