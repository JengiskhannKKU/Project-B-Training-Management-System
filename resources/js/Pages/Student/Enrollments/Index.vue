<script setup>
import { ref, computed, onMounted } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import axios from "axios";
import { useToast } from "vue-toastification";
import StudentLayout from "@/Layouts/StudentLayout.vue";
import LoadingSpinner from "@/Components/LoadingSpinner.vue";
import {
    BookOpen,
    GraduationCap,
    Award,
    Calendar,
    Clock,
    MapPin,
    UserRound,
} from "lucide-vue-next";

const toast = useToast();
const enrollments = ref([]);
const isLoading = ref(false);
const activeTab = ref("upcoming");
const cancellingId = ref(null);

const fetchEnrollments = async () => {
    isLoading.value = true;
    try {
        const { data } = await axios.get("/api/me/enrollments");
        enrollments.value = Array.isArray(data) ? data : [];
    } catch (error) {
        enrollments.value = [];
        toast.error("Unable to load enrollments.");
    } finally {
        isLoading.value = false;
    }
};

const normalizeDate = (value) => {
    if (!value) return null;
    const parsed = new Date(value);
    return Number.isNaN(parsed.getTime()) ? null : parsed;
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
        completed: { text: "Completed", class: "bg-gradient-to-r from-blue-600 to-indigo-600 shadow-lg" },
        cancelled: { text: "Cancelled", class: "bg-gray-600" },
    };
    return badges[status] || { text: "Unknown", class: "bg-gray-600" };
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

const isUpcoming = (session) => {
    const date = normalizeDate(session?.start_date);
    if (!date) return true;
    const start = new Date(date);
    start.setHours(0, 0, 0, 0);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return start >= today;
};

const canCancel = (enrollment) => {
    if (!enrollment || enrollment.status === "cancelled") return false;
    const date = normalizeDate(enrollment.session?.start_date);
    if (!date) return true;
    const start = new Date(date);
    start.setHours(0, 0, 0, 0);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return start > today;
};

const normalizedEnrollments = computed(() =>
    enrollments.value.map((enrollment) => {
        const session = enrollment.session || {};
        const program = session.program || {};
        return {
            ...enrollment,
            session,
            program,
        };
    })
);

const upcomingEnrollments = computed(() =>
    normalizedEnrollments.value.filter(
        (item) => item.status !== "completed"
    )
);

const finishedEnrollments = computed(() =>
    normalizedEnrollments.value.filter(
        (item) => item.status === "completed"
    )
);

const visibleEnrollments = computed(() =>
    activeTab.value === "upcoming"
        ? upcomingEnrollments.value
        : finishedEnrollments.value
);

const toAttendCount = computed(() => upcomingEnrollments.value.length);
const completeCount = computed(
    () =>
        normalizedEnrollments.value.filter(
            (item) => !isUpcoming(item.session) && item.status !== "cancelled"
        ).length
);
const certificateCount = computed(() => completeCount.value);

const cancelEnrollment = async (enrollment) => {
    if (!canCancel(enrollment)) return;
    cancellingId.value = enrollment.id;
    try {
        await axios.put(`/api/enrollments/${enrollment.id}/cancel`);
        enrollment.status = "cancelled";
        toast.success("Enrollment cancelled.");
    } catch (error) {
        toast.error(
            error?.response?.data?.message || "Unable to cancel enrollment."
        );
    } finally {
        cancellingId.value = null;
    }
};

onMounted(fetchEnrollments);
</script>

<template>
    <Head title="My Courses" />

    <StudentLayout>
        <div class="mx-auto max-w-5xl space-y-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">My Courses</h1>
                <p class="mt-1 text-sm text-gray-500">
                    All courses. Empower Your Trainee
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-2xl border border-gray-200 bg-white p-3 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                            <BookOpen class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="text-xl font-semibold text-gray-900">
                                {{ toAttendCount }}
                            </div>
                            <div class="text-sm text-gray-500">To Attend</div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-3 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                            <GraduationCap class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="text-xl font-semibold text-gray-900">
                                {{ completeCount }}
                            </div>
                            <div class="text-sm text-gray-500">Complete</div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-3 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-pink-100 text-pink-600">
                            <Award class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="text-xl font-semibold text-gray-900">
                                {{ certificateCount }}
                            </div>
                            <div class="text-sm text-gray-500">Certificates</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-2 shadow-sm">
                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="rounded-xl px-4 py-2 text-sm font-semibold"
                        :class="
                            activeTab === 'upcoming'
                                ? 'bg-emerald-50 text-emerald-700'
                                : 'text-gray-500'
                        "
                        @click="activeTab = 'upcoming'"
                    >
                        Upcoming ({{ toAttendCount }})
                    </button>
                    <button
                        type="button"
                        class="rounded-xl px-4 py-2 text-sm font-semibold"
                        :class="
                            activeTab === 'finished'
                                ? 'bg-emerald-50 text-emerald-700'
                                : 'text-gray-500'
                        "
                        @click="activeTab = 'finished'"
                    >
                        Finished ({{ finishedEnrollments.length }})
                    </button>
                </div>
            </div>

            <div v-if="isLoading" class="py-16">
                <LoadingSpinner size="lg" text="Loading enrollments..." />
            </div>

            <div v-else-if="visibleEnrollments.length === 0" class="py-24 text-center">
                <div class="text-lg font-semibold text-gray-900">No courses in this list</div>
                <p class="mt-2 text-sm text-gray-500">
                    You have no courses completed yet.
                </p>
            </div>

            <div v-else class="space-y-4">
                <div
                    v-for="enrollment in visibleEnrollments"
                    :key="enrollment.id"
                    class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm"
                >
                    <div class="grid gap-5 sm:grid-cols-[260px_minmax(0,1fr)]">
                        <div class="relative min-h-[160px] overflow-hidden rounded-xl border border-gray-100 sm:min-h-[180px]">
                            <img
                                v-if="enrollment.program?.image_url"
                                :src="enrollment.program.image_url"
                                :alt="enrollment.program?.name || 'Program'"
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
                                {{ getEnrollmentStatusBadge(enrollment.status).text }}
                            </span>
                        </div>

                        <div class="flex flex-col justify-between gap-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="text-xs font-semibold uppercase text-emerald-600">
                                        {{ enrollment.program?.category || "General" }}
                                    </div>
                                    <h3 class="mt-2 text-lg font-semibold text-gray-900">
                                        {{ enrollment.program?.name || enrollment.session?.title || "Course" }}
                                    </h3>
                                </div>
                                <div class="text-sm font-semibold text-gray-400">
                                    #{{ enrollment.program?.code || `C${enrollment.id}` }}
                                </div>
                            </div>

                            <div class="grid gap-3 text-sm text-gray-600 sm:grid-cols-2">
                                <div class="flex items-center gap-2">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600">
                                        <Calendar class="h-4 w-4" />
                                    </span>
                                    <div>
                                        <div class="text-xs text-gray-400">Date</div>
                                        <div class="font-semibold text-gray-900">
                                            {{ formatDate(enrollment.session?.start_date) }}
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="flex flex-col gap-1">
                                        <div class="text-xs text-gray-400">Session Status</div>
                                        <span
                                            :class="getSessionStatusBadge(enrollment.session?.status).class"
                                            class="inline-flex w-fit rounded-full px-2 py-0.5 text-xs font-semibold"
                                        >
                                            {{ getSessionStatusBadge(enrollment.session?.status).text }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-100 text-rose-600">
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
                                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-100 text-purple-600">
                                        <UserRound class="h-4 w-4" />
                                    </span>
                                    <div>
                                        <div class="text-xs text-gray-400">Trainer</div>
                                        <div class="font-semibold text-gray-900">
                                            {{ enrollment.session?.trainer || "Trainer Assigned" }}
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-fuchsia-100 text-fuchsia-600">
                                        <MapPin class="h-4 w-4" />
                                    </span>
                                    <div>
                                        <div class="text-xs text-gray-400">Location</div>
                                        <div class="font-semibold text-gray-900">
                                            {{ enrollment.session?.location || "Room 201" }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-100 pt-4">
                                <!-- If status is "pending", show only Cancel button -->
                                <template v-if="enrollment.status === 'pending'">
                                    <button
                                        type="button"
                                        class="rounded-full border border-rose-400 px-5 py-2 text-sm font-semibold text-rose-500 hover:bg-rose-50 disabled:opacity-60"
                                        :disabled="cancellingId === enrollment.id"
                                        @click="cancelEnrollment(enrollment)"
                                    >
                                        <LoadingSpinner
                                            v-if="cancellingId === enrollment.id"
                                            size="sm"
                                            color="gray"
                                            inline
                                        />
                                        <span>{{ cancellingId === enrollment.id ? "Cancelling..." : "Cancel Registration" }}</span>
                                    </button>
                                </template>

                                <!-- For other statuses, show View Details only -->
                                <template v-else>
                                    <Link
                                        :href="route('me.enrollments.show', enrollment.id)"
                                        class="rounded-full border border-emerald-400 px-5 py-2 text-sm font-semibold text-emerald-600 hover:bg-emerald-50"
                                    >
                                        View Details
                                    </Link>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
