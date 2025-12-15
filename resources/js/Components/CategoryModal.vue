<script setup>
import { ref, watch } from "vue";
import { X, ChevronDown, Tag, Code, Palette, Briefcase, TrendingUp, Database, BookOpen, Laptop, Lightbulb, Camera } from "lucide-vue-next";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    category: {
        type: Object,
        default: null,
    },
    mode: {
        type: String,
        default: "add", // 'add' or 'edit'
    },
});

const emit = defineEmits(["close", "save"]);

// Available icons
const availableIcons = [
    { name: "Tag", component: Tag },
    { name: "Code", component: Code },
    { name: "Palette", component: Palette },
    { name: "Briefcase", component: Briefcase },
    { name: "TrendingUp", component: TrendingUp },
    { name: "Database", component: Database },
    { name: "BookOpen", component: BookOpen },
    { name: "Laptop", component: Laptop },
    { name: "Lightbulb", component: Lightbulb },
    { name: "Camera", component: Camera },
];

// Available colors
const availableColors = [
    { name: "Blue", value: "blue", hex: "#3B82F6" },
    { name: "Purple", value: "purple", hex: "#A855F7" },
    { name: "Green", value: "green", hex: "#10B981" },
    { name: "Yellow", value: "yellow", hex: "#F59E0B" },
    { name: "Red", value: "red", hex: "#EF4444" },
    { name: "Pink", value: "pink", hex: "#EC4899" },
    { name: "Indigo", value: "indigo", hex: "#6366F1" },
    { name: "Teal", value: "teal", hex: "#14B8A6" },
    { name: "Orange", value: "orange", hex: "#F97316" },
    { name: "Cyan", value: "cyan", hex: "#06B6D4" },
];

const formData = ref({
    name: "",
    icon: "Tag",
    color: "blue",
});

const showIconDropdown = ref(false);

// Watch for category changes (for edit mode)
watch(
    () => props.category,
    (newCategory) => {
        if (newCategory) {
            formData.value = {
                name: newCategory.name || "",
                icon: newCategory.icon || "Tag",
                color: newCategory.color || "blue",
            };
        }
    },
    { immediate: true }
);

// Reset form when modal closes
watch(
    () => props.show,
    (newVal) => {
        if (!newVal) {
            if (props.mode === "add") {
                formData.value = {
                    name: "",
                    icon: "Tag",
                    color: "blue",
                };
            }
        }
    }
);

const selectIcon = (iconName) => {
    formData.value.icon = iconName;
    showIconDropdown.value = false;
};

const selectColor = (colorValue) => {
    formData.value.color = colorValue;
    // Keep dropdown open so user can select icon after color
};

const getIconComponent = (iconName) => {
    const icon = availableIcons.find((i) => i.name === iconName);
    return icon ? icon.component : Tag;
};

const getColorHex = (colorValue) => {
    const color = availableColors.find((c) => c.value === colorValue);
    return color ? color.hex : "#3B82F6";
};

const handleClose = () => {
    emit("close");
};

const handleSave = () => {
    emit("save", { ...formData.value });
};
</script>

<template>
    <!-- Modal Backdrop -->
    <Transition name="modal">
        <div
            v-if="show"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
            @click="handleClose"
        >
            <!-- Modal Content -->
            <div
                class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4"
                @click.stop
            >
                <!-- Modal Header -->
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">
                        {{ mode === "add" ? "Create Category" : "Edit Category" }}
                    </h2>
                    <button
                        @click="handleClose"
                        class="text-gray-400 hover:text-gray-600 transition-colors"
                    >
                        <X :size="24" />
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-5">
                    <!-- Name Field -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Category Name
                        </label>
                        <input
                            v-model="formData.name"
                            type="text"
                            placeholder="Enter category name"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#2f837d] focus:border-transparent outline-none transition-all"
                        />
                    </div>

                    <!-- Icon and Color Field -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Icon & Color
                        </label>
                        <div class="relative">
                            <button
                                @click="showIconDropdown = !showIconDropdown"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white flex items-center justify-between hover:border-gray-400 transition-colors"
                            >
                                <div class="flex items-center gap-2">
                                    <div
                                        class="w-8 h-8 rounded-full flex items-center justify-center"
                                        :style="{ backgroundColor: getColorHex(formData.color) + '20' }"
                                    >
                                        <component
                                            :is="getIconComponent(formData.icon)"
                                            :size="18"
                                            :style="{ color: getColorHex(formData.color) }"
                                        />
                                    </div>
                                    <span class="text-gray-700">{{ formData.icon }}</span>
                                </div>
                                <ChevronDown :size="20" class="text-gray-400" />
                            </button>

                            <!-- Icon Dropdown Menu -->
                            <Transition name="dropdown">
                                <div
                                    v-if="showIconDropdown"
                                    class="absolute top-full mt-2 w-full bg-white border border-gray-200 rounded-lg shadow-lg z-10 overflow-hidden"
                                >
                                    <!-- Color Selection Section -->
                                    <div class="p-3 border-b border-gray-200 bg-gray-50">
                                        <p class="text-xs font-medium text-gray-600 mb-2">Select Color</p>
                                        <div class="flex gap-2 flex-wrap">
                                            <button
                                                v-for="color in availableColors"
                                                :key="color.value"
                                                @click="selectColor(color.value)"
                                                class="w-7 h-7 rounded-full transition-all hover:scale-110"
                                                :class="{ 'ring-2 ring-offset-2 ring-gray-400': formData.color === color.value }"
                                                :style="{ backgroundColor: color.hex }"
                                                :title="color.name"
                                            ></button>
                                        </div>
                                    </div>

                                    <!-- Icon Selection Section -->
                                    <div class="max-h-60 overflow-y-auto">
                                        <button
                                            v-for="icon in availableIcons"
                                            :key="icon.name"
                                            @click="selectIcon(icon.name)"
                                            class="w-full px-4 py-2.5 flex items-center gap-2 hover:bg-gray-50 transition-colors"
                                            :class="{ 'bg-gray-50': formData.icon === icon.name }"
                                        >
                                            <component
                                                :is="icon.component"
                                                :size="20"
                                                class="text-gray-600"
                                            />
                                            <span class="text-gray-700">{{ icon.name }}</span>
                                        </button>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-end gap-3 p-6 border-t border-gray-200">
                    <button
                        @click="handleClose"
                        class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors"
                    >
                        Cancel
                    </button>
                    <button
                        @click="handleSave"
                        class="px-5 py-2.5 bg-[#2f837d] text-white rounded-lg font-medium hover:bg-[#26685f] transition-colors"
                    >
                        {{ mode === "add" ? "Create" : "Save Changes" }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
/* Modal transitions */
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

/* Dropdown transitions */
.dropdown-enter-active,
.dropdown-leave-active {
    transition: all 0.2s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}
</style>
