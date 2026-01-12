<script setup>
import { ref, computed, onMounted } from "vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import CategoryCard from "@/Components/CategoryCard.vue";
import CategoryModal from "@/Components/CategoryModal.vue";
import { Plus } from "lucide-vue-next";
import axios from "axios";
import { useToast } from "vue-toastification";

const page = usePage();
const toast = useToast();

const categories = ref([]);
const isLoading = ref(false);

// Fetch categories from API
const fetchCategories = async () => {
    isLoading.value = true;
    try {
        await axios.get('/sanctum/csrf-cookie');
        const { data } = await axios.get('/api/categories');
        
        // Map API response to match component expectations
        categories.value = (data?.data || data || []).map(cat => ({
            id: cat.id,
            name: cat.name,
            description: '', // Not in current API schema
            courses: cat.courses_count || 0,
            color: cat.color || '#6b7280',
            icon: cat.icon_name || 'Folder',
        }));
    } catch (error) {
        console.error('Failed to fetch categories:', error);
        toast.error('Failed to load categories');
    } finally {
        isLoading.value = false;
    }
};

// Fetch on component mount
onMounted(() => {
    fetchCategories();
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
            return categories.value.find((c) => c.id === categoryId) || null;
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
        await axios.get('/sanctum/csrf-cookie');
        
        if (modalMode.value === "add") {
            // Create new category via API
            const { data } = await axios.post('/api/categories', {
                name: categoryData.name,
                icon_name: categoryData.icon,
                color: categoryData.color,
            });
            
            toast.success('Category created successfully!');
        } else {
            // Update existing category via API
            const { data } = await axios.put(`/api/categories/${selectedCategory.value.id}`, {
                name: categoryData.name,
                icon_name: categoryData.icon,
                color: categoryData.color,
            });
            
            toast.success('Category updated successfully!');
        }
        
        // Refresh categories list
        await fetchCategories();
        
        // Close modal after successful save
        handleCloseModal();
    } catch (error) {
        console.error('Failed to save category:', error);
        const message = error?.response?.data?.message || error?.message || 'Failed to save category';
        toast.error(message);
    }
};

const handleDeleteCategory = async (category) => {
    if (confirm(`Are you sure you want to delete "${category.name}"?`)) {
        try {
            await axios.get('/sanctum/csrf-cookie');
            await axios.delete(`/api/categories/${category.id}`);
            
            toast.success('Category deleted successfully!');
            
            // Refresh categories list
            await fetchCategories();
        } catch (error) {
            console.error('Failed to delete category:', error);
            const message = error?.response?.data?.message || error?.message || 'Failed to delete category';
            toast.error(message);
        }
    }
};
</script>

<template>
    <Head title="Categories" />
    <AdminLayout>
        <div
            class="flex flex-col h-full gap-6 bg-white border border-[#dfe5ef] rounded-[25px] p-6"
        >
            <div class="flex items-center justify-between flex-shrink-0">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Categories</h1>
                    <p class="mt-2 text-sm text-gray-600">
                        {{ $t('Organize courses into categories') }}
                    </p>
                </div>
                <button
                    @click="handleCreateCategory"
                    class="bg-[#2f837d] hover:bg-[#26685f] text-white px-6 py-2.5 rounded-lg font-medium transition-all flex items-center gap-2 shadow-sm hover:shadow-md"
                >
                    <Plus :size="20" />
                    <span>Create Category</span>
                </button>
            </div>

            <!-- Categories Grid -->
            <div
                class="grid grid-cols-[repeat(auto-fill,256px)] gap-x-4 gap-y-3 overflow-auto h-[calc(100vh-300px)] content-start"
            >
                <CategoryCard
                    v-for="category in categories"
                    :key="category.id"
                    :category="category"
                    @edit="handleEditCategory"
                    @delete="handleDeleteCategory"
                />
            </div>
        </div>

        <!-- Category Modal -->
        <CategoryModal
            :show="showModal"
            :mode="modalMode"
            :category="selectedCategory"
            @close="handleCloseModal"
            @save="handleSaveCategory"
        />
    </AdminLayout>
</template>
