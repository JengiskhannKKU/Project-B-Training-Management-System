<script setup>
import { ref, computed } from "vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import CategoryCard from "@/Components/CategoryCard.vue";
import CategoryModal from "@/Components/CategoryModal.vue";
import { Plus } from "lucide-vue-next";
import axios from "axios";

const page = usePage();

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
});

// Detect modal state from current route
const currentPath = computed(() => page.url);

const showModal = computed(() => {
    return (
        currentPath.value.includes("/create") ||
        currentPath.value.includes("/edit")
    );
});

const modalMode = computed(() => {
    return currentPath.value.includes("/create") ? "add" : "edit";
});

const selectedCategory = computed(() => {
    if (modalMode.value === "edit") {
        // Extract category ID from URL like /admin/categories/3/edit
        const match = currentPath.value.match(/\/admin\/categories\/(\d+)\/edit/);
        if (match) {
            const categoryId = parseInt(match[1]);
            return props.categories.find((c) => c.id === categoryId) || null;
        }
    }
    return null;
});

const handleCreateCategory = () => {
    router.visit(route("admin.categories.create"), {
        preserveScroll: true,
        preserveState: true,
    });
};

const handleEditCategory = (category) => {
    router.visit(route("admin.categories.edit", category.id), {
        preserveScroll: true,
        preserveState: true,
    });
};

const handleCloseModal = () => {
    router.visit(route("admin.categories"), {
        preserveScroll: true,
        preserveState: true,
    });
};

const handleSaveCategory = async (categoryData) => {
    try {
        if (modalMode.value === "add") {
            // Create new category
            await axios.post("/api/admin/categories", categoryData);
        } else {
            // Update existing category
            await axios.put(
                `/api/admin/categories/${selectedCategory.value.id}`,
                categoryData
            );
        }
        // Close modal and reload page to show updated data
        router.visit(route("admin.categories"), {
            preserveScroll: true,
        });
    } catch (error) {
        console.error("Error saving category:", error);
        if (error.response?.data?.errors) {
            alert(
                "Validation error: " +
                Object.values(error.response.data.errors).flat().join(", ")
            );
        } else {
            alert("Failed to save category. Please try again.");
        }
    }
};

const handleDeleteCategory = async (category) => {
    if (confirm(`Are you sure you want to delete "${category.name}"?`)) {
        try {
            await axios.delete(`/api/admin/categories/${category.id}`);
            // Reload page to show updated data
            router.visit(route("admin.categories"), {
                preserveScroll: true,
            });
        } catch (error) {
            console.error("Error deleting category:", error);
            if (error.response?.data?.message) {
                alert(error.response.data.message);
            } else {
                alert("Failed to delete category. Please try again.");
            }
        }
    }
};
</script>

<template>

    <Head title="Categories" />
    <AdminLayout>
        <div class="flex flex-col h-full gap-6 bg-white border border-[#dfe5ef] rounded-[25px] p-6">
            <div class="flex items-center justify-between flex-shrink-0">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Categories</h1>
                    <p class="mt-2 text-sm text-gray-600">
                        {{ $t('Organize courses into categories') }}
                    </p>
                </div>
                <button @click="handleCreateCategory"
                    class="bg-[#2f837d] hover:bg-[#26685f] text-white px-6 py-2.5 rounded-lg font-medium transition-all flex items-center gap-2 shadow-sm hover:shadow-md">
                    <Plus :size="20" />
                    <span>Create Category</span>
                </button>
            </div>

            <!-- Categories Grid -->
            <div
                class="grid grid-cols-[repeat(auto-fill,256px)] gap-x-4 gap-y-3 overflow-auto h-[calc(100vh-300px)] content-start">
                <CategoryCard v-for="category in props.categories" :key="category.id" :category="category"
                    @edit="handleEditCategory" @delete="handleDeleteCategory" />
            </div>
        </div>

        <!-- Category Modal -->
        <CategoryModal :show="showModal" :mode="modalMode" :category="selectedCategory" @close="handleCloseModal"
            @save="handleSaveCategory" />
    </AdminLayout>
</template>
