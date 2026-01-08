<script setup>
import { ref, computed } from "vue";
import StarRating from "@/Components/StarRating.vue";
import { Send } from "lucide-vue-next";

const props = defineProps({
    existingReview: {
        type: Object,
        default: null,
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["submit", "cancel"]);

const rating = ref(props.existingReview?.rating || 0);
const comment = ref(props.existingReview?.comment || "");

const isEditing = computed(() => !!props.existingReview);

const isValid = computed(() => rating.value >= 1 && rating.value <= 5);

const handleSubmit = () => {
    if (!isValid.value) return;

    emit("submit", {
        rating: rating.value,
        comment: comment.value.trim() || null,
    });
};

const handleCancel = () => {
    emit("cancel");
};
</script>

<template>
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h3 class="mb-4 text-lg font-semibold text-gray-900">
            {{ isEditing ? "Edit Your Review" : "Leave a Review" }}
        </h3>

        <div class="space-y-4">
            <!-- Rating -->
            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">
                    Your Rating <span class="text-red-500">*</span>
                </label>
                <div class="flex items-center gap-3">
                    <StarRating v-model="rating" size="lg" />
                    <span v-if="rating > 0" class="text-sm text-gray-600">
                        {{ rating }} / 5
                    </span>
                </div>
                <p v-if="rating === 0" class="mt-1 text-sm text-gray-500">
                    Click to rate
                </p>
            </div>

            <!-- Comment -->
            <div>
                <label
                    for="review-comment"
                    class="mb-2 block text-sm font-medium text-gray-700"
                >
                    Your Feedback (Optional)
                </label>
                <textarea
                    id="review-comment"
                    v-model="comment"
                    rows="4"
                    class="w-full rounded-lg border border-gray-300 px-4 py-3 text-gray-700 placeholder-gray-400 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-200"
                    placeholder="Share your experience with this course..."
                    maxlength="2000"
                ></textarea>
                <p class="mt-1 text-right text-xs text-gray-500">
                    {{ comment.length }} / 2000
                </p>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <button
                    v-if="isEditing"
                    type="button"
                    class="rounded-lg px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100"
                    @click="handleCancel"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    :disabled="!isValid || loading"
                    :class="[
                        'inline-flex items-center gap-2 rounded-lg px-5 py-2.5 text-sm font-medium transition-colors',
                        isValid && !loading
                            ? 'bg-teal-600 text-white hover:bg-teal-700'
                            : 'cursor-not-allowed bg-gray-300 text-gray-500',
                    ]"
                    @click="handleSubmit"
                >
                    <Send class="h-4 w-4" />
                    {{ loading ? "Submitting..." : isEditing ? "Update Review" : "Submit Review" }}
                </button>
            </div>
        </div>
    </div>
</template>
