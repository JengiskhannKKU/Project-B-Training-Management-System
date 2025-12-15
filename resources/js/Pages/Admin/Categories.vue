<script setup>
import { ref, computed } from "vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import CategoryCard from "@/Components/CategoryCard.vue";
import CategoryModal from "@/Components/CategoryModal.vue";
import { Plus } from "lucide-vue-next";

const page = usePage();

const categories = ref([
    {
        id: 1,
        name: "Programming",
        description: "Software development and programming courses",
        courses: 12,
        color: "blue",
        icon: "Code",
    },
    {
        id: 2,
        name: "Design",
        description: "UI/UX and graphic design courses",
        courses: 8,
        color: "purple",
        icon: "Palette",
    },
    {
        id: 3,
        name: "Business",
        description: "Business management and leadership",
        courses: 15,
        color: "green",
        icon: "Briefcase",
    },
    {
        id: 4,
        name: "Marketing",
        description: "Digital marketing and SEO",
        courses: 6,
        color: "yellow",
        icon: "TrendingUp",
    },
    {
        id: 5,
        name: "Data Science",
        description: "Analytics, ML, and AI courses",
        courses: 10,
        color: "red",
        icon: "Database",
    },
    {
        id: 6,
        name: "Photography",
        description: "Photography and videography courses",
        courses: 7,
        color: "cyan",
        icon: "Camera",
    },
    {
        id: 7,
        name: "Innovation",
        description: "Creative thinking and innovation",
        courses: 5,
        color: "orange",
        icon: "Lightbulb",
    },
    {
        id: 8,
        name: "Technology",
        description: "General technology courses",
        courses: 9,
        color: "indigo",
        icon: "Laptop",
    },
]);

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

const handleSaveCategory = (categoryData) => {
    if (modalMode.value === "add") {
        // Add new category
        const newId = Math.max(...categories.value.map((c) => c.id)) + 1;
        categories.value.push({
            id: newId,
            ...categoryData,
            description: "",
            courses: 0,
        });
    } else {
        // Edit existing category
        const index = categories.value.findIndex(
            (c) => c.id === selectedCategory.value.id
        );
        if (index !== -1) {
            categories.value[index] = {
                ...categories.value[index],
                ...categoryData,
            };
        }
    }
    // Close modal and clear URL after save
    handleCloseModal();
};

const handleDeleteCategory = (category) => {
    if (confirm(`Are you sure you want to delete "${category.name}"?`)) {
        const index = categories.value.findIndex((c) => c.id === category.id);
        if (index !== -1) {
            categories.value.splice(index, 1);
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
                        Organize courses into categories
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
