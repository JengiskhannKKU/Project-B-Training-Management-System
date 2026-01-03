<script setup>
import { ref, onMounted } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import axios from "axios";
import { useToast } from "vue-toastification";
import { Plus, Pencil, Trash2, Image } from "lucide-vue-next";
import TrainerLayout from "@/Layouts/TrainerLayout.vue";
import LoadingSpinner from "@/Components/LoadingSpinner.vue";
import ConfirmationDialog from "@/Components/ConfirmationDialog.vue";

const toast = useToast();

const templates = ref([]);
const isLoading = ref(false);
const showDeleteModal = ref(false);
const deleteTarget = ref(null);
const isDeleting = ref(false);

const ensureCsrf = () => axios.get("/sanctum/csrf-cookie");

const fetchTemplates = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get("/api/trainer/certificate-templates");
        templates.value = response.data?.data ?? [];
    } catch (error) {
        templates.value = [];
        const message =
            error?.response?.data?.message ||
            "Unable to load certificate templates.";
        toast.error(message);
    } finally {
        isLoading.value = false;
    }
};

const openDeleteModal = (template) => {
    deleteTarget.value = template;
    showDeleteModal.value = true;
};

const deleteTemplate = async () => {
    if (!deleteTarget.value) {
        return;
    }

    isDeleting.value = true;
    try {
        await ensureCsrf();
        await axios.delete(`/api/trainer/certificate-templates/${deleteTarget.value.id}`);
        toast.success("Certificate template deleted.");
        templates.value = templates.value.filter(
            (item) => item.id !== deleteTarget.value.id
        );
        showDeleteModal.value = false;
    } catch (error) {
        const message =
            error?.response?.data?.message ||
            "Failed to delete certificate template.";
        toast.error(message);
    } finally {
        isDeleting.value = false;
    }
};

const scopeLabel = (scope) => {
    if (scope === "program") return "Program";
    if (scope === "session") return "Session";
    return "Global";
};

const formatDate = (value) => {
    if (!value) return "—";
    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) {
        return value;
    }
    return parsed.toLocaleDateString();
};

onMounted(fetchTemplates);
</script>

<template>
    <Head title="Certificate Templates" />
    <TrainerLayout>
        <div class="space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">
                        Certificate Templates
                    </h1>
                    <p class="text-sm text-gray-600">
                        Manage certificate layouts for your programs and sessions.
                    </p>
                </div>
                <Link
                    href="/trainer/certificate-templates/create"
                    class="inline-flex items-center gap-2 rounded-lg bg-[#2f837d] px-4 py-2 text-sm font-semibold text-white hover:bg-[#266a66]"
                >
                    <Plus class="h-4 w-4" />
                    Create Template
                </Link>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Templates ({{ templates.length }})
                    </h2>
                </div>

                <div v-if="isLoading" class="py-10">
                    <LoadingSpinner size="lg" text="Loading templates..." />
                </div>

                <div v-else-if="templates.length === 0" class="py-10 text-center text-sm text-gray-500">
                    No certificate templates yet.
                </div>

                <div v-else class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Name
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Scope
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Program / Session
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Background
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Active
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Updated
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Action
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="template in templates" :key="template.id">
                                <td class="px-4 py-3 text-gray-900">
                                    {{ template.name }}
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ scopeLabel(template.scope) }}
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    <div v-if="template.scope === 'program'">
                                        {{ template.program?.name || '—' }}
                                    </div>
                                    <div v-else-if="template.scope === 'session'">
                                        {{ template.session?.title || '—' }}
                                        <div class="text-xs text-gray-400">
                                            {{ template.program?.name || '—' }}
                                        </div>
                                    </div>
                                    <div v-else>Global</div>
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold',
                                            template.has_background
                                                ? 'bg-emerald-100 text-emerald-700'
                                                : 'bg-gray-100 text-gray-500'
                                        ]"
                                    >
                                        <Image class="h-3.5 w-3.5" />
                                        {{ template.has_background ? 'Uploaded' : 'None' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        :class="[
                                            'inline-flex rounded-full px-2 py-0.5 text-xs font-semibold',
                                            template.is_active
                                                ? 'bg-green-100 text-green-700'
                                                : 'bg-gray-100 text-gray-500'
                                        ]"
                                    >
                                        {{ template.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ formatDate(template.updated_at) }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <Link
                                            :href="`/trainer/certificate-templates/${template.id}/edit`"
                                            class="inline-flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                        >
                                            <Pencil class="h-3.5 w-3.5" />
                                            Edit
                                        </Link>
                                        <button
                                            type="button"
                                            @click="openDeleteModal(template)"
                                            class="inline-flex items-center gap-1 rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <ConfirmationDialog
            :show="showDeleteModal"
            title="Delete Template"
            message="This will permanently delete the certificate template."
            confirm-text="Delete"
            confirm-button-class="bg-red-600 hover:bg-red-700"
            @confirm="deleteTemplate"
            @close="showDeleteModal = false"
        />

        <div v-if="isDeleting" class="fixed inset-0 z-40 bg-black/10"></div>
    </TrainerLayout>
</template>
