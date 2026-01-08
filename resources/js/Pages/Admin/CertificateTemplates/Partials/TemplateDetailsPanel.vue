<script setup>
import { computed } from "vue";
import { Settings2 } from "lucide-vue-next";

const props = defineProps({
    modelValue: {
        type: Object,
        required: true,
    },
    programs: {
        type: Array,
        default: () => [],
    },
    sessions: {
        type: Array,
        default: () => [],
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(["update:modelValue", "fetchSessions"]);

const form = computed({
    get: () => props.modelValue,
    set: (value) => emit("update:modelValue", value),
});

const updateField = (field, value) => {
    emit("update:modelValue", { ...props.modelValue, [field]: value });
};

const handleScopeChange = (event) => {
    const value = event.target.value;
    updateField("scope", value);

    if (value === "global") {
        emit("update:modelValue", {
            ...props.modelValue,
            scope: value,
            program_id: "",
            session_id: "",
        });
    } else if (value === "program") {
        emit("update:modelValue", {
            ...props.modelValue,
            scope: value,
            session_id: "",
        });
    }
};

const handleProgramChange = (event) => {
    const value = event.target.value;
    updateField("program_id", value);

    if (props.modelValue.scope === "session") {
        emit("fetchSessions", value);
    }
};
</script>

<template>
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm space-y-4 overflow-hidden transition-shadow hover:shadow-md">
        <!-- Header -->
        <div class="flex items-center gap-3 px-6 pt-6">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-[#2f837d]/10 to-[#2f837d]/5">
                <Settings2 class="h-5 w-5 text-[#2f837d]" />
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Template Details</h2>
                <p class="text-sm text-gray-500">Core metadata and scope.</p>
            </div>
        </div>

        <div class="px-6 pb-6 space-y-4">
            <!-- Template Name -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Template name</label>
                <input
                    :value="form.name"
                    @input="updateField('name', $event.target.value)"
                    type="text"
                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-[#2f837d]"
                    placeholder="Default Program Certificate"
                    required
                />
                <p v-if="errors.name" class="mt-1 text-xs text-red-500">{{ errors.name }}</p>
            </div>

            <!-- Scope -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Scope</label>
                <select
                    :value="form.scope"
                    @change="handleScopeChange"
                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-[#2f837d]"
                >
                    <option value="global">Global</option>
                    <option value="program">Program</option>
                    <option value="session">Session</option>
                </select>
                <p v-if="errors.scope" class="mt-1 text-xs text-red-500">{{ errors.scope }}</p>
            </div>

            <!-- Program (shown for program/session scope) -->
            <div v-if="form.scope !== 'global'">
                <label class="block text-sm font-medium text-gray-700">Program</label>
                <select
                    :value="form.program_id"
                    @change="handleProgramChange"
                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-[#2f837d]"
                    :required="form.scope !== 'global'"
                >
                    <option value="">Select program</option>
                    <option v-for="program in programs" :key="program.id" :value="String(program.id)">
                        {{ program.name }}
                    </option>
                </select>
                <p v-if="errors.program_id" class="mt-1 text-xs text-red-500">{{ errors.program_id }}</p>
            </div>

            <!-- Session (shown for session scope) -->
            <div v-if="form.scope === 'session'">
                <label class="block text-sm font-medium text-gray-700">Session</label>
                <select
                    :value="form.session_id"
                    @input="updateField('session_id', $event.target.value)"
                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-[#2f837d]"
                    :disabled="!form.program_id"
                    required
                >
                    <option value="">Select session</option>
                    <option v-for="session in sessions" :key="session.id" :value="String(session.id)">
                        {{ session.title }}
                    </option>
                </select>
                <p v-if="errors.session_id" class="mt-1 text-xs text-red-500">{{ errors.session_id }}</p>
                <p v-if="!form.program_id" class="mt-1 text-xs text-gray-500">
                    Select a program to load sessions.
                </p>
            </div>

            <!-- Active Toggle -->
            <div class="flex items-center justify-between py-3 border-t border-gray-100">
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-gray-900">Active Template</span>
                    <span class="text-xs text-gray-500">Enable to use this template for certificates</span>
                </div>
                <button
                    type="button"
                    @click="updateField('is_active', !form.is_active)"
                    :class="[
                        form.is_active ? 'bg-[#2f837d]' : 'bg-gray-200',
                        'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-[#2f837d] focus:ring-offset-2'
                    ]"
                >
                    <span
                        :class="[
                            form.is_active ? 'translate-x-5' : 'translate-x-0',
                            'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                        ]"
                    />
                </button>
            </div>
        </div>
    </div>
</template>
