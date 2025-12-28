<script setup>
import { ref, computed, onMounted } from "vue";
import { Head } from "@inertiajs/vue3";
import axios from "axios";
import { useToast } from "vue-toastification";
import StudentLayout from "@/Layouts/StudentLayout.vue";
import LoadingSpinner from "@/Components/LoadingSpinner.vue";
import ErrorBanner from "@/Components/ErrorBanner.vue";
import CourseCard from "@/Components/CourseCard.vue";
import { Search, ListFilterIcon, ArrowDownNarrowWide, BookOpen } from "lucide-vue-next";

const toast = useToast();

const programs = ref([]);
const isLoading = ref(false);
const errorMessage = ref(null);
const searchQuery = ref("");
const selectedCategory = ref("all");

const fetchPrograms = async () => {
    isLoading.value = true;
    errorMessage.value = null;
    try {
        const { data } = await axios.get("/api/catalog/programs");
        programs.value = data || [];
    } catch (error) {
        programs.value = [];
        const message = error?.response?.data?.message || "Unable to load courses. Please try again.";
        errorMessage.value = message;
        toast.error(message);
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

                <ErrorBanner
                    :show="errorMessage !== null"
                    :message="errorMessage"
                    @dismiss="errorMessage = null"
                    class="mt-4"
                />

                <div v-if="isLoading" class="mt-6 py-16">
                    <LoadingSpinner size="lg" text="Loading courses..." />
                </div>

                <div
                    v-else
                    class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <CourseCard
                        v-for="program in filteredPrograms"
                        :key="program.id"
                        :id="program.id"
                        :href="`/programs/${program.id}`"
                        :name="program.name || 'Untitled program'"
                        :image_url="program.image_url || ''"
                        :rating="program.rating ?? 4.6"
                        :level="program.level || 'beginner'"
                        :students_count="program.students_count ?? 32"
                        :price="program.price ?? 'Free'"
                        :date="program.date ?? 'Jan 5-10, 2026'"
                        :time="program.time ?? '09:00 - 16:00'"
                        :location="program.location ?? 'Smart Classroom'"
                    />
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
