<script setup>
import { ref, computed } from "vue";
import { Tag, Edit2, Trash2, BookOpen, Code, Palette, Briefcase, TrendingUp, Database, Laptop, Lightbulb, Camera } from "lucide-vue-next";

const props = defineProps({
    category: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(["edit", "delete"]);

const showActions = ref(false);

const toggleActions = () => {
    showActions.value = !showActions.value;
};

const handleEdit = () => {
    emit("edit", props.category);
};

const handleDelete = () => {
    emit("delete", props.category);
};

// Icon mapping
const iconComponents = {
    Tag,
    Code,
    Palette,
    Briefcase,
    TrendingUp,
    Database,
    BookOpen,
    Laptop,
    Lightbulb,
    Camera,
};

// Color mapping - must match CategoryModal.vue
const colorMapping = {
    blue: { hex: "#3B82F6", light: "#DBEAFE" },
    purple: { hex: "#A855F7", light: "#F3E8FF" },
    green: { hex: "#10B981", light: "#D1FAE5" },
    yellow: { hex: "#F59E0B", light: "#FEF3C7" },
    red: { hex: "#EF4444", light: "#FEE2E2" },
    pink: { hex: "#EC4899", light: "#FCE7F3" },
    indigo: { hex: "#6366F1", light: "#E0E7FF" },
    teal: { hex: "#14B8A6", light: "#CCFBF1" },
    orange: { hex: "#F97316", light: "#FFEDD5" },
    cyan: { hex: "#06B6D4", light: "#CFFAFE" },
};

const categoryIcon = computed(() => {
    return iconComponents[props.category.icon] || Tag;
});

const categoryColor = computed(() => {
    return colorMapping[props.category.color] || colorMapping.blue;
});
</script>

<template>
    <div
        @click="toggleActions"
        class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow flex flex-col relative group cursor-pointer"
        style="width: 256px; height: 240px"
    >
        <!-- Centered Content -->
        <div
            class="flex-grow flex flex-col items-center justify-center text-center"
        >
            <div
                class="w-12 h-12 rounded-full flex items-center justify-center mb-3"
                :style="{ backgroundColor: categoryColor.light }"
            >
                <component
                    :is="categoryIcon"
                    :size="20"
                    :style="{ color: categoryColor.hex }"
                />
            </div>
            <h3 class="text-sm font-semibold text-gray-900 mb-1.5">
                {{ category.name }}
            </h3>
            <div class="flex items-center text-xs text-gray-500">
                <BookOpen :size="14" class="mr-1" />
                {{ category.courses }} courses
            </div>
        </div>

        <!-- Action Buttons - Bottom Right -->
        <div
            :class="[
                'absolute bottom-3 right-3 flex space-x-1.5 bg-[#787486]/20 p-2 rounded-lg transition-opacity duration-200',
                'opacity-0 group-hover:opacity-100',
                showActions ? 'opacity-100' : '',
            ]"
        >
            <button
                class="text-gray-400 hover:text-[#2f837d] transition-colors"
                @click.stop="handleEdit"
            >
                <Edit2 :size="16" />
            </button>
            <button
                class="text-gray-400 hover:text-red-600 transition-colors"
                @click.stop="handleDelete"
            >
                <Trash2 :size="16" />
            </button>
        </div>
    </div>
</template>
