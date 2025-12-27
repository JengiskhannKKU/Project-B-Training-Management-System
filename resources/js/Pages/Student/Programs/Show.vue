<script setup>
import { ref, computed, onMounted } from "vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import axios from "axios";
import { useToast } from "vue-toastification";
import StudentLayout from "@/Layouts/StudentLayout.vue";
import LoadingSpinner from "@/Components/LoadingSpinner.vue";
import ErrorBanner from "@/Components/ErrorBanner.vue";
import {
    Calendar,
    Clock,
    MapPin,
    UserRound,
    Share2,
    Mail,
    QrCode,
} from "lucide-vue-next";

const toast = useToast();
const page = usePage();

const props = defineProps({
    programId: {
        type: [String, Number],
        required: true,
    },
});

const program = ref(null);
const sessions = ref([]);
const isLoadingProgram = ref(false);
const isLoadingSessions = ref(false);
const programError = ref(null);
const sessionsError = ref(null);
const showConfirm = ref(false);
const showSuccess = ref(false);
const selectedSession = ref(null);
const enrollLoading = ref(false);

const programTitle = computed(() => program.value?.name || "Program");

const programCategory = computed(() => program.value?.category || "General");

const openSessions = computed(() =>
    sessions.value.filter((session) => {
        const status = (session?.status || "").toLowerCase();
        return status === "open";
    })
);

const userRole = computed(() => page.props.auth?.user?.role?.name || "");

const backLink = computed(() =>
    userRole.value === "student" ? "/student" : "/programs"
);

const backLinkText = computed(() =>
    userRole.value === "student" ? "Back to My Courses" : "Back to Courses"
);

const programDescription = computed(
    () =>
        program.value?.description ||
        "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer vitae congue ullam consectetur ornare consectetur sed in leo. Enim imperdiet urna tincidunt at integer nunc amet vitae orci."
);

const formattedDuration = computed(() => {
    if (program.value?.duration_hours === null || program.value?.duration_hours === undefined) {
        return "-";
    }
    return `${program.value.duration_hours} hrs`;
});

const whatYouLearn = computed(() => [
    "Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
    "Integer vitae congue ullam consectetur ornare consectetur sed in leo.",
    "Enim imperdiet urna tincidunt at integer nunc amet vitae orci.",
    "Ultrices augue scelerisque.",
]);

const whoShouldAttend = computed(() => [
    "Lorem ipsum dolor sit amet, consectetur adipiscing elit.",
    "Integer vitae congue ullam consectetur ornare consectetur sed in leo.",
    "Enim imperdiet urna tincidunt at integer nunc amet vitae orci.",
    "Ultrices augue scelerisque.",
]);

const fetchProgram = async () => {
    isLoadingProgram.value = true;
    programError.value = null;
    try {
        const { data } = await axios.get("/api/catalog/programs");
        const list = data || [];
        const match = list.find((item) => String(item.id) === String(props.programId));
        program.value = match || null;
        if (!match) {
            programError.value = "Program not found.";
        }
    } catch (error) {
        program.value = null;
        const message = error?.response?.data?.message || "Unable to load program details.";
        programError.value = message;
        toast.error(message);
    } finally {
        isLoadingProgram.value = false;
    }
};

const fetchSessions = async () => {
    isLoadingSessions.value = true;
    sessionsError.value = null;
    try {
        const { data } = await axios.get(
            `/api/catalog/programs/${props.programId}/sessions`
        );
        sessions.value = data || [];
    } catch (error) {
        sessions.value = [];
        const message = error?.response?.data?.message || "Unable to load sessions.";
        sessionsError.value = message;
        toast.error(message);
    } finally {
        isLoadingSessions.value = false;
    }
};

const openEnrollModal = (session) => {
    selectedSession.value = session;
    showConfirm.value = true;
    showSuccess.value = false;
};

const closeEnrollModal = () => {
    showConfirm.value = false;
    selectedSession.value = null;
};

const confirmEnroll = async () => {
    if (!selectedSession.value) return;
    enrollLoading.value = true;
    try {
        await axios.post("/api/enrollments", {
            session_id: selectedSession.value.id,
        });
        showConfirm.value = false;
        showSuccess.value = true;
        fetchSessions();
    } catch (error) {
        toast.error(
            error?.response?.data?.message ||
                "Unable to enroll in this session."
        );
    } finally {
        enrollLoading.value = false;
    }
};

const closeSuccessModal = () => {
    showSuccess.value = false;
    selectedSession.value = null;
};

const formattedDate = (value) => {
    if (!value) return "-";
    return value;
};

const formatSessionMeta = (session) => {
    const date = formattedDate(session.start_date);
    const time = formattedTime(session);
    const location = session.location || "Smart Classroom";
    return `${date} · ${time} · ${location}`;
};

const formattedTime = (session) => {
    const start = session?.start_time ? session.start_time.slice(0, 5) : "--:--";
    const end = session?.end_time ? session.end_time.slice(0, 5) : "--:--";
    return `${start} - ${end}`;
};

onMounted(() => {
    fetchProgram();
    fetchSessions();
});
</script>

<template>
    <Head :title="programTitle" />

    <StudentLayout>
        <div class="mx-auto max-w-6xl space-y-6">
            <Link
                :href="backLink"
                class="inline-flex items-center gap-2 text-sm font-medium text-teal-600 hover:text-teal-700"
            >
                <span class="text-lg">←</span>
                {{ backLinkText }}
            </Link>

            <ErrorBanner
                :show="programError !== null"
                :message="programError"
                @dismiss="programError = null"
            />

            <div class="rounded-2xl overflow-hidden">
                    <img
                        v-if="program?.image_url"
                        :src="program.image_url"
                        :alt="programTitle"
                        class="h-48 w-full object-cover sm:h-64"
                    />
                    <div v-else class="h-48 w-full bg-gradient-to-br from-cyan-200 via-teal-300 to-teal-500 sm:h-64">
                        <svg class="h-full w-full" viewBox="0 0 1200 400" preserveAspectRatio="none">
                            <defs>
                                <linearGradient id="studentProgramGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                    <stop offset="0%" style="stop-color:rgb(207,250,254);stop-opacity:0.4" />
                                    <stop offset="100%" style="stop-color:rgb(45,212,191);stop-opacity:0.4" />
                                </linearGradient>
                            </defs>
                            <path fill="url(#studentProgramGradient)" d="M0,128 C150,150 350,100 600,120 C850,140 1000,160 1200,140 L1200,400 L0,400 Z" opacity="0.5"/>
                            <path fill="url(#studentProgramGradient)" d="M0,192 C200,170 400,220 600,200 C800,180 1000,190 1200,200 L1200,400 L0,400 Z" opacity="0.5"/>
                            <path fill="rgba(255,255,255,0.1)" d="M0,256 C200,230 400,270 600,250 C800,230 1000,250 1200,240 L1200,400 L0,400 Z"/>
                        </svg>
                    </div>
                </div>

                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h1 class="text-3xl font-semibold text-gray-900">
                            {{ programTitle }}
                        </h1>
                        <p class="mt-2 text-sm text-gray-500">
                            {{ programDescription }}
                        </p>
                    </div>
                    <span
                        class="rounded-full px-3 py-1 text-xs font-semibold"
                        :class="programCategory === 'General' ? 'bg-gray-100 text-gray-600' : 'bg-rose-100 text-rose-700'"
                    >
                        {{ programCategory }}
                    </span>
                </div>

                <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500">
                    <span class="rounded-full bg-emerald-100 px-3 py-1 font-semibold text-emerald-700">Published</span>
                    <span class="rounded-full bg-blue-100 px-3 py-1 font-semibold text-blue-700">Registration Open</span>
                    <span class="rounded-full bg-amber-100 px-3 py-1 font-semibold text-amber-700">Start in 15 days</span>
                </div>

                <div class="grid gap-6 lg:grid-cols-[2fr,1fr]">
                    <div class="space-y-6">
                        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                            <h2 class="text-lg font-semibold text-gray-900">
                                Description
                            </h2>
                            <p class="mt-3 text-sm text-gray-500">
                                {{ programDescription }}
                            </p>
                            <div class="mt-6">
                                <h3 class="text-sm font-semibold text-gray-900">What You'll Learn</h3>
                                <ul class="mt-3 space-y-2 text-sm text-gray-500">
                                    <li v-for="item in whatYouLearn" :key="item" class="flex items-start gap-2">
                                        <span class="mt-1 h-1.5 w-1.5 rounded-full bg-[#2f837d]"></span>
                                        <span>{{ item }}</span>
                                    </li>
                                </ul>
                            </div>
                            <div class="mt-6">
                                <h3 class="text-sm font-semibold text-gray-900">Who Should Attend</h3>
                                <ul class="mt-3 space-y-2 text-sm text-gray-500">
                                    <li v-for="item in whoShouldAttend" :key="item" class="flex items-start gap-2">
                                        <span class="mt-1 h-1.5 w-1.5 rounded-full bg-[#2f837d]"></span>
                                        <span>{{ item }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                            <h2 class="text-lg font-semibold text-gray-900">Select Session</h2>

                            <ErrorBanner
                                v-if="sessionsError"
                                :show="sessionsError !== null"
                                :message="sessionsError"
                                @dismiss="sessionsError = null"
                                class="mt-4"
                            />

                            <LoadingSpinner v-if="isLoadingSessions" text="Loading sessions..." class="mt-4" />

                            <div v-else class="mt-4 space-y-4">
                            <div
                                v-for="session in openSessions"
                                :key="session.id"
                                class="flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
                            >
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900">
                                            {{ session.title || "Session" }}
                                        </div>
                                        <div class="mt-1 text-xs text-gray-500">
                                            {{ formatSessionMeta(session) }}
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="rounded-full bg-emerald-100 px-2 py-1 text-[10px] font-semibold text-emerald-700">
                                            5 seats available
                                        </span>
                                        <button
                                            type="button"
                                            class="rounded-full border border-[#2f837d] px-4 py-2 text-sm font-semibold text-[#2f837d] hover:bg-[#2f837d] hover:text-white"
                                            @click="openEnrollModal(session)"
                                        >
                                            register
                                        </button>
                                    </div>
                                </div>

                            <div v-if="openSessions.length === 0" class="text-sm text-gray-500">
                                No open sessions available.
                            </div>
                            </div>
                            <div class="mt-4 space-y-2 text-xs text-gray-500">
                                <div class="flex items-center gap-2">
                                    <span class="h-4 w-4 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-[10px]">✓</span>
                                    Free Registration
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="h-4 w-4 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-[10px]">✓</span>
                                    Certificate upon completion
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    Course Information
                                </h3>
                            </div>
                            <div class="mt-4 space-y-3 text-sm text-gray-600">
                                <div class="flex justify-between">
                                    <span>Category:</span>
                                    <span class="font-semibold text-gray-800">
                                        {{ programCategory }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Level:</span>
                                    <span class="font-semibold text-gray-800">Advance</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Period:</span>
                                    <span class="font-semibold text-gray-800">May 1 - MAY 2</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Time:</span>
                                    <span class="font-semibold text-gray-800">09:00 - 12:00 AM</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Location:</span>
                                    <span class="font-semibold text-gray-800">Smart Classroom</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Trainer:</span>
                                    <span class="font-semibold text-gray-800">Natthiya Chakaew</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Certificated:</span>
                                    <span class="font-semibold text-gray-800">Standard</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Status:</span>
                                    <span class="font-semibold text-emerald-600">OPEN</span>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Instructor</h3>
                                    <p class="text-sm text-gray-500">สมชาย ใจดี</p>
                                </div>
                                <div class="text-right text-sm text-gray-500">
                                    <div class="text-lg font-semibold text-gray-900">4.8 ★</div>
                                    <div>(16,124 reviews)</div>
                                </div>
                            </div>
                            <div class="mt-4 flex items-center gap-4">
                                <div class="h-12 w-12 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-700 font-semibold">
                                    aT
                                </div>
                                <div class="text-sm text-gray-500">
                                    <div class="font-semibold text-gray-900">85%</div>
                                    Positive
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                            <h3 class="text-lg font-semibold text-gray-900">Course URL</h3>
                            <div class="mt-4 flex items-center gap-2 rounded-xl border border-gray-200 px-3 py-2 text-sm text-gray-600">
                                https://example.com/courses/ux*de
                            </div>
                            <div class="mt-4 grid grid-cols-3 gap-2 text-xs text-gray-500">
                                <button class="flex flex-col items-center gap-1 rounded-xl border border-gray-200 px-3 py-2 hover:bg-gray-50">
                                    <Share2 class="h-4 w-4 text-gray-500" />
                                    Share
                                </button>
                                <button class="flex flex-col items-center gap-1 rounded-xl border border-gray-200 px-3 py-2 hover:bg-gray-50">
                                    <Mail class="h-4 w-4 text-gray-500" />
                                    Email
                                </button>
                                <button class="flex flex-col items-center gap-1 rounded-xl border border-gray-200 px-3 py-2 hover:bg-gray-50">
                                    <QrCode class="h-4 w-4 text-gray-500" />
                                    QR Code
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    <div
        v-if="showConfirm"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
        @click.self="closeEnrollModal"
    >
        <div class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl">
            <h3 class="text-center text-xl font-semibold text-gray-900">
                Confirm Registration
            </h3>
            <div class="mt-4 rounded-2xl bg-rose-50 p-4 text-center">
                <div class="text-sm font-semibold text-gray-800">
                    {{ programTitle }}
                </div>
                <span class="mt-2 inline-flex rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-600">
                    {{ programCategory }}
                </span>
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div class="rounded-2xl border border-gray-200 p-3">
                    <div class="flex items-center gap-2 text-xs text-gray-500">
                        <Calendar class="h-4 w-4" />
                        Date
                    </div>
                    <div class="mt-1 text-sm font-semibold text-gray-900">
                        {{ formattedDate(selectedSession?.start_date) }}
                    </div>
                </div>
                <div class="rounded-2xl border border-gray-200 p-3">
                    <div class="flex items-center gap-2 text-xs text-gray-500">
                        <Clock class="h-4 w-4" />
                        Time
                    </div>
                    <div class="mt-1 text-sm font-semibold text-gray-900">
                        {{ formattedTime(selectedSession) }}
                    </div>
                </div>
            </div>

            <div class="mt-4 rounded-2xl bg-emerald-50 p-4 text-sm text-emerald-700">
                <div class="flex items-center gap-2">
                    <MapPin class="h-4 w-4" />
                    {{ selectedSession?.location || "Location to be announced" }}
                </div>
                <div class="mt-2 flex items-center gap-2">
                    <UserRound class="h-4 w-4" />
                    Trainer assigned
                </div>
            </div>

            <div class="mt-4 rounded-2xl bg-amber-50 p-3 text-center text-xs text-amber-700">
                Please double-check the date and time before registering.
            </div>

            <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                <button
                    type="button"
                    class="flex-1 rounded-full border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50"
                    @click="closeEnrollModal"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    class="flex-1 rounded-full bg-[#2f837d] px-4 py-2 text-sm font-semibold text-white hover:bg-[#266a66] disabled:opacity-60"
                    :disabled="enrollLoading"
                    @click="confirmEnroll"
                >
                    <LoadingSpinner v-if="enrollLoading" size="sm" color="white" inline />
                    <span>{{ enrollLoading ? "Registering..." : "Confirm register" }}</span>
                </button>
            </div>
        </div>
    </div>

    <div
        v-if="showSuccess"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
        @click.self="closeSuccessModal"
    >
        <div class="w-full max-w-lg rounded-3xl bg-white p-8 text-center shadow-2xl">
            <div class="mx-auto h-20 w-20 rounded-full bg-emerald-100 flex items-center justify-center">
                <div class="h-12 w-12 rounded-full bg-emerald-500 text-white flex items-center justify-center text-2xl">
                    ✓
                </div>
            </div>
            <h3 class="mt-6 text-xl font-semibold text-gray-900">Success!</h3>
            <p class="mt-2 text-sm text-gray-500">
                Your action was completed successfully. Great job!
            </p>
            <button
                type="button"
                class="mt-6 w-full rounded-full bg-emerald-500 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-600"
                @click="closeSuccessModal"
            >
                Continue
            </button>
        </div>
    </div>
    </StudentLayout>
</template>
