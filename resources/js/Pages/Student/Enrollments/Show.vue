<script setup>
import { ref, computed, onMounted } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import axios from "axios";
import { useToast } from "vue-toastification";
import StudentLayout from "@/Layouts/StudentLayout.vue";
import LoadingSpinner from "@/Components/LoadingSpinner.vue";
import {
    BookOpen,
    Calendar,
    Clock,
    MapPin,
    UserRound,
    ArrowLeft,
    CheckCircle2,
    XCircle,
    AlertCircle,
} from "lucide-vue-next";

const props = defineProps({
    enrollmentId: {
        type: [String, Number],
        required: true,
    },
});

const toast = useToast();
const enrollment = ref(null);
const attendances = ref([]);
const isLoading = ref(false);

const fetchEnrollmentDetails = async () => {
    isLoading.value = true;
    try {
        // Fetch enrollment from the list endpoint
        const { data: enrollments } = await axios.get("/api/me/enrollments");
        enrollment.value = enrollments.find(
            (e) => e.id === parseInt(props.enrollmentId)
        );

        if (!enrollment.value) {
            toast.error("Enrollment not found.");
            return;
        }

        // Fetch attendance records
        const { data: attendanceData } = await axios.get(
            `/api/enrollments/${props.enrollmentId}/attendances`
        );
        attendances.value = Array.isArray(attendanceData.data)
            ? attendanceData.data
            : [];
    } catch (error) {
        toast.error("Unable to load enrollment details.");
    } finally {
        isLoading.value = false;
    }
};

const formatDate = (value) => {
    if (!value) return "-";
    return value;
};

const formatTime = (session) => {
    const start = session?.start_time ? session.start_time.slice(0, 5) : "--:--";
    const end = session?.end_time ? session.end_time.slice(0, 5) : "--:--";
    return `${start} - ${end}`;
};

const getEnrollmentStatusBadge = (status) => {
    const badges = {
        pending: { text: "Pending", class: "bg-yellow-600" },
        confirmed: { text: "Confirmed", class: "bg-emerald-600" },
        completed: {
            text: "Completed",
            class: "bg-gradient-to-r from-blue-600 to-indigo-600 shadow-lg",
        },
        cancelled: { text: "Cancelled", class: "bg-gray-600" },
    };
    return badges[status] || { text: "Registered", class: "bg-emerald-600" };
};

const getSessionStatusBadge = (status) => {
    const badges = {
        upcoming: { text: "Upcoming", class: "bg-indigo-100 text-indigo-700" },
        open: { text: "Open", class: "bg-green-100 text-green-700" },
        closed: { text: "Closed", class: "bg-gray-100 text-gray-700" },
        completed: { text: "Completed", class: "bg-purple-100 text-purple-700" },
        cancelled: { text: "Cancelled", class: "bg-red-100 text-red-700" },
    };
    return badges[status] || { text: status, class: "bg-gray-100 text-gray-700" };
};

const getAttendanceStatusIcon = (status) => {
    const icons = {
        present: { component: CheckCircle2, class: "text-green-600" },
        absent: { component: XCircle, class: "text-red-600" },
        late: { component: AlertCircle, class: "text-yellow-600" },
    };
    return (
        icons[status] || { component: AlertCircle, class: "text-gray-600" }
    );
};

const getAttendanceStatusBadge = (status) => {
    const badges = {
        present: { text: "Present", class: "bg-green-100 text-green-700" },
        absent: { text: "Absent", class: "bg-red-100 text-red-700" },
        late: { text: "Late", class: "bg-yellow-100 text-yellow-700" },
    };
    return badges[status] || { text: status, class: "bg-gray-100 text-gray-700" };
};

const attendanceSummary = computed(() => {
    if (!attendances.value.length) return null;

    const present = attendances.value.filter((a) => a.status === "present").length;
    const absent = attendances.value.filter((a) => a.status === "absent").length;
    const late = attendances.value.filter((a) => a.status === "late").length;
    const total = attendances.value.length;

    return { present, absent, late, total };
});

onMounted(fetchEnrollmentDetails);
</script>

<template>
    <Head title="Enrollment Details" />

    <StudentLayout>
        <div class="mx-auto max-w-5xl space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <Link
                        :href="route('me.enrollments')"
                        class="mb-2 inline-flex items-center gap-2 text-sm font-semibold text-emerald-600 hover:text-emerald-700"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        Back to My Courses
                    </Link>
                    <h1 class="text-2xl font-semibold text-gray-900">
                        Enrollment Details
                    </h1>
                </div>
            </div>

            <div v-if="isLoading" class="py-16">
                <LoadingSpinner size="lg" text="Loading details..." />
            </div>

            <div v-else-if="!enrollment" class="py-24 text-center">
                <div class="text-lg font-semibold text-gray-900">
                    Enrollment not found
                </div>
                <p class="mt-2 text-sm text-gray-500">
                    The enrollment you're looking for doesn't exist or you don't
                    have access to it.
                </p>
            </div>

            <div v-else class="space-y-6">
                <!-- Course Information Card -->
                <div
                    class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
                >
                    <div class="grid gap-6 sm:grid-cols-[260px_minmax(0,1fr)]">
                        <div
                            class="relative min-h-[160px] overflow-hidden rounded-xl border border-gray-100 sm:min-h-[180px]"
                        >
                            <img
                                v-if="enrollment.session?.program?.image_url"
                                :src="enrollment.session.program.image_url"
                                :alt="enrollment.session?.program?.name || 'Program'"
                                class="h-full w-full object-cover"
                            />
                            <div
                                v-else
                                class="flex h-full items-center justify-center bg-gradient-to-br from-emerald-100 via-teal-200 to-cyan-200 text-sm font-semibold text-emerald-700"
                            >
                                Training Management
                            </div>
                            <span
                                :class="getEnrollmentStatusBadge(enrollment.status).class"
                                class="absolute left-3 top-3 rounded-full px-3 py-1 text-xs font-semibold text-white"
                            >
                                {{
                                    getEnrollmentStatusBadge(enrollment.status).text
                                }}
                            </span>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <div
                                    class="text-xs font-semibold uppercase text-emerald-600"
                                >
                                    {{ enrollment.session?.program?.category || "General" }}
                                </div>
                                <h2 class="mt-2 text-xl font-semibold text-gray-900">
                                    {{
                                        enrollment.session?.program?.name ||
                                        enrollment.session?.title ||
                                        "Course"
                                    }}
                                </h2>
                                <p
                                    v-if="enrollment.session?.program?.description"
                                    class="mt-2 text-sm text-gray-600"
                                >
                                    {{ enrollment.session.program.description }}
                                </p>
                            </div>

                            <div class="grid gap-3 text-sm sm:grid-cols-2">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600"
                                    >
                                        <Calendar class="h-4 w-4" />
                                    </span>
                                    <div>
                                        <div class="text-xs text-gray-400">Date</div>
                                        <div class="font-semibold text-gray-900">
                                            {{
                                                formatDate(
                                                    enrollment.session?.start_date
                                                )
                                            }}
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <div class="flex flex-col gap-1">
                                        <div class="text-xs text-gray-400">
                                            Session Status
                                        </div>
                                        <span
                                            :class="
                                                getSessionStatusBadge(
                                                    enrollment.session?.status
                                                ).class
                                            "
                                            class="inline-flex w-fit rounded-full px-2 py-0.5 text-xs font-semibold"
                                        >
                                            {{
                                                getSessionStatusBadge(
                                                    enrollment.session?.status
                                                ).text
                                            }}
                                        </span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <span
                                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-100 text-rose-600"
                                    >
                                        <Clock class="h-4 w-4" />
                                    </span>
                                    <div>
                                        <div class="text-xs text-gray-400">Time</div>
                                        <div class="font-semibold text-gray-900">
                                            {{ formatTime(enrollment.session) }}
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <span
                                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-100 text-purple-600"
                                    >
                                        <UserRound class="h-4 w-4" />
                                    </span>
                                    <div>
                                        <div class="text-xs text-gray-400">Trainer</div>
                                        <div class="font-semibold text-gray-900">
                                            {{
                                                enrollment.session?.trainer ||
                                                "Trainer Assigned"
                                            }}
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <span
                                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-fuchsia-100 text-fuchsia-600"
                                    >
                                        <MapPin class="h-4 w-4" />
                                    </span>
                                    <div>
                                        <div class="text-xs text-gray-400">Location</div>
                                        <div class="font-semibold text-gray-900">
                                            {{
                                                enrollment.session?.location || "Room 201"
                                            }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attendance Records Card -->
                <div
                    class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
                >
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Attendance Records
                        </h3>
                        <span
                            v-if="attendanceSummary"
                            class="text-sm text-gray-500"
                        >
                            {{ attendanceSummary.present }} /
                            {{ attendanceSummary.total }} attended
                        </span>
                    </div>

                    <div v-if="attendances.length === 0" class="py-12 text-center">
                        <div class="text-sm font-semibold text-gray-900">
                            No attendance records yet
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            Attendance will be recorded during the session
                        </p>
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            v-for="attendance in attendances"
                            :key="attendance.id"
                            class="flex items-center justify-between rounded-xl border border-gray-100 bg-gray-50 p-4"
                        >
                            <div class="flex items-center gap-3">
                                <component
                                    :is="getAttendanceStatusIcon(attendance.status).component"
                                    :class="getAttendanceStatusIcon(attendance.status).class"
                                    class="h-5 w-5"
                                />
                                <div>
                                    <div class="font-semibold text-gray-900">
                                        {{
                                            formatDate(
                                                attendance.checked_at ||
                                                    attendance.created_at
                                            )
                                        }}
                                    </div>
                                    <div
                                        v-if="attendance.note"
                                        class="mt-1 text-xs text-gray-500"
                                    >
                                        {{ attendance.note }}
                                    </div>
                                </div>
                            </div>
                            <span
                                :class="getAttendanceStatusBadge(attendance.status).class"
                                class="rounded-full px-3 py-1 text-xs font-semibold"
                            >
                                {{ getAttendanceStatusBadge(attendance.status).text }}
                            </span>
                        </div>
                    </div>

                    <!-- Attendance Summary -->
                    <div
                        v-if="attendanceSummary"
                        class="mt-6 grid gap-3 border-t border-gray-200 pt-6 sm:grid-cols-3"
                    >
                        <div class="rounded-xl bg-green-50 p-3 text-center">
                            <div class="text-2xl font-semibold text-green-700">
                                {{ attendanceSummary.present }}
                            </div>
                            <div class="text-xs text-green-600">Present</div>
                        </div>
                        <div class="rounded-xl bg-red-50 p-3 text-center">
                            <div class="text-2xl font-semibold text-red-700">
                                {{ attendanceSummary.absent }}
                            </div>
                            <div class="text-xs text-red-600">Absent</div>
                        </div>
                        <div class="rounded-xl bg-yellow-50 p-3 text-center">
                            <div class="text-2xl font-semibold text-yellow-700">
                                {{ attendanceSummary.late }}
                            </div>
                            <div class="text-xs text-yellow-600">Late</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
