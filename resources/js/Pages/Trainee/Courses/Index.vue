<script setup>
import { ref, computed, onMounted } from "vue";
import { Head } from "@inertiajs/vue3";
import axios from "axios";
import { useNotification } from "@/composables/useNotification";
import TraineeLayout from "@/Layouts/TraineeLayout.vue";
import Skeleton from "@/Components/Skeleton.vue";
import ErrorBanner from "@/Components/ErrorBanner.vue";
import CourseCard from "@/Components/CourseCard.vue";
import FilterDropdown from "@/Components/FilterDropdown.vue";
import SortDropdown from "@/Components/SortDropdown.vue";
import ExportModal from "@/Components/ExportModal.vue";
import { Search, ListFilterIcon, ArrowDownNarrowWide, BookOpen, Share } from "lucide-vue-next";
import jsPDF from "jspdf";
import "jspdf-autotable";

const notify = useNotification();

const programs = ref([]);
const isLoading = ref(false);
const errorMessage = ref(null);
const searchQuery = ref("");
const selectedCategory = ref([]);
const selectedStatus = ref([]);
const sortColumn = ref("created_at");
const sortDirection = ref("desc");
const showExportModal = ref(false);

const fetchPrograms = async () => {
    isLoading.value = true;
    errorMessage.value = null;
    try {
        const { data } = await axios.get("/api/catalog/courses");
        programs.value = data || [];
    } catch (error) {
        programs.value = [];
        const message = error?.response?.data?.message || "Unable to load courses. Please try again.";
        errorMessage.value = message;
        notify.error(message);
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    fetchPrograms();
});

const categories = computed(() => {
    const unique = new Set(programs.value.map((program) => program.category || "General"));
    return [...unique];
});

const filteredPrograms = computed(() => {
    let result = programs.value;

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter((program) =>
            (program.name || "").toLowerCase().includes(query)
        );
    }

    if (selectedCategory.value.length > 0) {
        result = result.filter(
            (program) => selectedCategory.value.includes(program.category || "General")
        );
    }

    if (sortColumn.value) {
        result.sort((a, b) => {
            let aVal = a[sortColumn.value];
            let bVal = b[sortColumn.value];

            if (sortColumn.value === 'created_at') {
                 // Date sorting
                 const dateA = a.created_at ? new Date(a.created_at).getTime() : 0;
                 const dateB = b.created_at ? new Date(b.created_at).getTime() : 0;
                 aVal = dateA;
                 bVal = dateB;
            } else if (typeof aVal === "string") {
                aVal = aVal.toLowerCase();
                bVal = (bVal).toLowerCase();
            }

            if (sortDirection.value === "asc") {
                return aVal > bVal ? 1 : -1;
            } else {
                return aVal < bVal ? 1 : -1;
            }
        });
    }

    return result;
});

const handleSort = ({ column, direction }) => {
    sortColumn.value = column;
    sortDirection.value = direction;
};

const resetFilters = () => {
    selectedCategory.value = [];
    selectedStatus.value = [];
};

const resetSort = () => {
    sortColumn.value = "";
    sortDirection.value = "asc";
};

const formatDuration = (hours) => {
    if (!hours && hours !== 0) return "-";
    return `${hours} hrs`;
};

// Export to CSV
const exportToCSV = () => {
    const headers = ["ID", "Name", "Category", "Level", "Students", "Price", "Date"];
    const csvData = filteredPrograms.value.map((program) => [
        program.id,
        program.name,
        program.category,
        program.level,
        program.trainees_count,
        program.price,
        program.date,
    ]);

    const csvContent = [
        headers.join(","),
        ...csvData.map((row) => row.join(",")),
    ].join("\n");

    const blob = new Blob([csvContent], { type: "text/csv" });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = "programs.csv";
    a.click();
    window.URL.revokeObjectURL(url);
    showExportModal.value = false;
};

// Export to PDF
const exportToPDF = () => {
    try {
        const doc = new jsPDF();

        // Add title
        doc.setFontSize(16);
        doc.text('Programs Report', 14, 20);

        // Add generation date
        doc.setFontSize(10);
        doc.text(`Generated: ${new Date().toLocaleDateString()}`, 14, 28);

        // Prepare table data with null-safe values
        const headers = [['ID', 'Name', 'Category', 'Level', 'Students', 'Price', 'Date']];
        const data = filteredPrograms.value.map((program) => [
            program.id ?? '',
            program.name ?? '',
            program.category ?? '',
            program.level ?? '',
            program.trainees_count ?? 0,
            program.price ?? '',
            program.date ?? '',
        ]);

        // Generate table
        doc.autoTable({
            head: headers,
            body: data,
            startY: 35,
            theme: 'grid',
            headStyles: { fillColor: [59, 130, 246] },
            styles: { fontSize: 9 },
        });

        // Save the PDF
        doc.save('programs.pdf');
    } catch (error) {
        console.error('Error generating PDF:', error);
        alert('Failed to generate PDF. Please try again.');
    } finally {
        showExportModal.value = false;
    }
};

</script>

<template>
    <Head title="All Courses" />

    <TraineeLayout>
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
                        <FilterDropdown
                            v-model:selectedDepartment="selectedCategory"
                            v-model:selectedStatus="selectedStatus"
                            :departments="categories"
                            departmentLabel="Category"
                            @reset="resetFilters"
                        >
                            <template #trigger>
                                <button
                                    class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-4 py-2 text-sm transition-colors"
                                    :class="(selectedCategory.length > 0 || selectedStatus.length > 0) ? 'bg-[#2f837d]/10 border-[#2f837d] text-[#2f837d]' : 'bg-white text-gray-600 hover:bg-gray-50'"
                                >
                                    <ListFilterIcon class="h-4 w-4" />
                                    Filter
                                    <span v-if="selectedCategory.length > 0 || selectedStatus.length > 0" class="ml-1 font-semibold">
                                        ({{ selectedCategory.length + selectedStatus.length }})
                                    </span>
                                </button>
                            </template>
                        </FilterDropdown>

                        <SortDropdown
                            :sortColumn="sortColumn"
                            :sortDirection="sortDirection"
                            :sortOptions="[
                                { value: 'created_at', label: 'Date' },
                                { value: 'name', label: 'Course Name' },
                                { value: 'duration', label: 'Duration' },
                                { value: 'rating', label: 'Rating' },
                            ]"
                            @sort="handleSort"
                            @reset="resetSort"
                        >
                            <template #trigger>
                                <button
                                    class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-4 py-2 text-sm transition-colors"
                                    :class="sortColumn ? 'bg-[#2f837d]/10 border-[#2f837d] text-[#2f837d]' : 'bg-white text-gray-600 hover:bg-gray-50'"
                                >
                                    <ArrowDownNarrowWide class="h-4 w-4" />
                                    Sort
                                    <span v-if="sortColumn" class="ml-1 font-medium text-xs opacity-90">
                                        : {{ sortColumn.charAt(0).toUpperCase() + sortColumn.slice(1) }}
                                    </span>
                                </button>
                            </template>
                        </SortDropdown>

                        <!-- Share/Export button -->
                        <button
                            @click="showExportModal = true"
                            class="rounded-lg border border-[#d5dde7] inline-flex gap-2 items-center px-4 py-2 hover:bg-gray-50 transition-colors"
                        >
                            <Share class="h-4 w-4" />
                            <p>Export</p>
                        </button>
                    </div>
                </div>

                <!-- Removed old category buttons -->

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

                <!-- Skeleton Loaders -->
                <div
                    v-if="isLoading"
                    class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <div v-for="n in 6" :key="n" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <!-- Image skeleton -->
                        <Skeleton variant="rectangular" width="100%" height="192px" />
                        <div class="p-4 space-y-3">
                            <!-- Title skeleton -->
                            <Skeleton variant="text" width="80%" height="20px" />
                            <!-- Rating skeleton -->
                            <Skeleton variant="text" width="40%" height="16px" />
                            <!-- Description skeleton -->
                            <Skeleton variant="text" :rows="2" />
                            <!-- Price skeleton -->
                            <Skeleton variant="text" width="30%" height="18px" />
                        </div>
                    </div>
                </div>

                <!-- Actual Content -->
                <div
                    v-else
                    class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <CourseCard
                        v-for="program in filteredPrograms"
                        :key="program.id"
                        :id="program.id"
                        :href="`/courses/${program.code}`"
                        :name="program.name || 'Untitled program'"
                        :image_url="program.image_url || ''"
                        :rating="program.rating ?? 4.6"
                        :level="program.level || 'beginner'"
                        :trainees_count="program.trainees_count ?? 32"
                        :price="program.price ?? 'Free'"
                        :date="program.date ?? 'Jan 5-10, 2026'"
                        :time="program.time ?? '09:00 - 16:00'"
                        :location="program.location ?? 'Smart Classroom'"
                        :status="program.status"
                    />
                </div>

                <div
                    v-if="!isLoading && filteredPrograms.length === 0"
                    class="mt-6 rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-6 py-10 text-center text-sm text-gray-500"
                >
                    No courses found.
                </div>
            </div>

            <!-- Export Modal -->
            <ExportModal
                :show="showExportModal"
                activeTab="programs"
                dataType="programs"
                @close="showExportModal = false"
                @exportCSV="exportToCSV"
                @exportPDF="exportToPDF"
            />
        </div>
    </TraineeLayout>
</template>
