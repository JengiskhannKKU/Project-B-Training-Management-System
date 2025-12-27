<script setup>
import { ref, computed, onMounted } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import axios from "axios";
import StudentLayout from "@/Layouts/StudentLayout.vue";
import { Search, ListFilterIcon, ArrowDownNarrowWide, BookOpen } from "lucide-vue-next";

const programs = ref([]);
const isLoading = ref(false);
const searchQuery = ref("");
const selectedCategory = ref("all");

const fetchPrograms = async () => {
    isLoading.value = true;
    try {
        const { data } = await axios.get("/api/catalog/programs");
        programs.value = data || [];
    } catch (error) {
        programs.value = [];
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    fetchPrograms();
});

const categories = computed(() => {
    const unique = new Set(programs.value.map((program) => program.category || "General"));
    return ["all", ...unique];
});

const filteredPrograms = computed(() => {
    let result = programs.value;

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter((program) =>
            (program.name || "").toLowerCase().includes(query)
        );
    }

    if (selectedCategory.value !== "all") {
        result = result.filter(
            (program) => (program.category || "General") === selectedCategory.value
        );
    }

    return result;
});

const formatDuration = (hours) => {
    if (!hours && hours !== 0) return "-";
    return `${hours} hrs`;
};

const categoryClass = (category) => {
    const colorMap = {
        Design: "bg-rose-50 text-rose-700",
        "IT & Software": "bg-blue-50 text-blue-700",
        Marketing: "bg-amber-50 text-amber-700",
        "Data Science": "bg-indigo-50 text-indigo-700",
        General: "bg-gray-100 text-gray-600",
    };
    return colorMap[category] || "bg-gray-100 text-gray-600";
};
</script>

<template>
    <Head title="All Courses" />

    <StudentLayout>
        <div class="space-y-6">
            <div>
                <h1 class="text-3xl font-semibold text-gray-900">All Courses</h1>
                <p class="mt-1 text-sm text-gray-500">
                    All courses. Empower your trainee
                </p>
            </div>

            <div class="rounded-[28px] border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex-1">
                        <div class="relative max-w-xl">
                            <Search class="absolute left-4 top-3 h-5 w-5 text-gray-400" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search courses..."
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 pl-12 pr-4 text-sm focus:border-[#2f837d] focus:ring-[#2f837d]"
                            />
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"
                        >
                            <ListFilterIcon class="h-4 w-4" />
                            Filter
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"
                        >
                            <ArrowDownNarrowWide class="h-4 w-4" />
                            Sort
                        </button>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap gap-2">
                    <button
                        v-for="category in categories"
                        :key="category"
                        type="button"
                        @click="selectedCategory = category"
                        :class="[
                            'rounded-full px-4 py-2 text-sm font-medium transition',
                            selectedCategory === category
                                ? 'bg-[#DAFFED] text-[#2F837D]'
                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200',
                        ]"
                    >
                        {{ category === 'all' ? 'All courses' : category }}
                    </button>
                </div>

                <div class="mt-6 flex items-center gap-2 text-lg font-semibold text-gray-900">
                    <BookOpen class="h-5 w-5 text-[#2f837d]" />
                    <span>All Courses ({{ filteredPrograms.length }})</span>
                </div>

                <div
                    v-if="isLoading"
                    class="mt-6 rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-6 py-10 text-center text-sm text-gray-500"
                >
                    Loading courses...
                </div>

                <div
                    v-else
                    class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <Link
                        v-for="program in filteredPrograms"
                        :key="program.id"
                        :href="`/programs/${program.id}`"
                        class="block"
                    >
                        <article
                            class="rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-md"
                        >
                        <div class="h-40 overflow-hidden rounded-t-2xl bg-gradient-to-br from-[#c9f7e6] via-[#e8faf4] to-[#fff]">
                            <img
                                v-if="program.image_url"
                                :src="program.image_url"
                                alt=""
                                class="h-full w-full object-cover"
                            />
                            <div
                                v-else
                                class="flex h-full items-center justify-center text-sm font-semibold text-[#2f837d]"
                            >
                                Training program
                            </div>
                        </div>
                        <div class="p-5 space-y-3">
                            <div class="flex items-center justify-between">
                                <span
                                    class="rounded-full px-3 py-1 text-xs font-semibold"
                                    :class="categoryClass(program.category || 'General')"
                                >
                                    {{ program.category || 'General' }}
                                </span>
                                <span class="text-xs text-gray-400">
                                    {{ formatDuration(program.duration_hours) }}
                                </span>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ program.name || 'Untitled program' }}
                            </h3>
                            <div class="text-sm text-gray-500">
                                Duration: <span class="font-medium text-gray-700">{{ formatDuration(program.duration_hours) }}</span>
                            </div>
                        </div>
                        </article>
                    </Link>
                </div>

                <div
                    v-if="!isLoading && filteredPrograms.length === 0"
                    class="mt-6 rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-6 py-10 text-center text-sm text-gray-500"
                >
                    No courses found.
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
