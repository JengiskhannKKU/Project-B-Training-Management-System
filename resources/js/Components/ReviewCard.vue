<script setup>
import { Star, ThumbsUp, MessageCircle, Bookmark, Share2 } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
    review: {
        type: Object,
        required: true
    }
});

const isHelpful = ref(false);
const isSaved = ref(false);

const toggleHelpful = () => {
    isHelpful.value = !isHelpful.value;
};

const toggleSaved = () => {
    isSaved.value = !isSaved.value;
};

const handleReply = () => {
    // Placeholder for reply functionality
    console.log('Reply to review:', props.review.id);
};

const handleShare = () => {
    // Placeholder for share functionality
    console.log('Share review:', props.review.id);
};

// Generate avatar initials
const getInitials = (name) => {
    return name
        .split(' ')
        .map(word => word.charAt(0))
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

// Generate avatar color based on name
const getAvatarColor = (name) => {
    const colors = [
        'bg-gradient-to-br from-blue-500 to-blue-600',
        'bg-gradient-to-br from-purple-500 to-purple-600',
        'bg-gradient-to-br from-pink-500 to-pink-600',
        'bg-gradient-to-br from-indigo-500 to-indigo-600',
        'bg-gradient-to-br from-teal-500 to-teal-600',
        'bg-gradient-to-br from-green-500 to-green-600',
        'bg-gradient-to-br from-orange-500 to-orange-600',
    ];
    const index = name.charCodeAt(0) % colors.length;
    return colors[index];
};
</script>

<template>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition-shadow">
        <!-- Reviewer Info -->
        <div class="flex items-start gap-4 mb-4">
            <!-- Avatar -->
            <div :class="[
                'w-12 h-12 rounded-full flex items-center justify-center text-white font-semibold flex-shrink-0',
                getAvatarColor(review.reviewerName)
            ]">
                {{ getInitials(review.reviewerName) }}
            </div>

            <!-- Name, Date, Rating -->
            <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-2 flex-wrap">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">
                            {{ review.reviewerName }}
                        </h3>
                        <p class="text-xs text-gray-500">
                            {{ review.relativeTime }}
                        </p>
                    </div>

                    <!-- Star Rating -->
                    <div class="flex items-center gap-1">
                        <Star
                            v-for="i in 5"
                            :key="i"
                            :size="16"
                            :class="i <= review.rating ? 'text-yellow-400 fill-yellow-400' : 'text-gray-300'"
                        />
                    </div>
                </div>

                <!-- Course Name -->
                <div class="mt-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-teal-100 text-teal-800">
                        {{ review.courseName }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Review Text -->
        <p class="text-sm text-gray-700 leading-relaxed mb-4">
            {{ review.reviewText }}
        </p>

        <!-- Action Buttons -->
        <div class="flex items-center gap-2 pt-3 border-t border-gray-100">
            <button
                @click="toggleHelpful"
                :class="[
                    'flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors',
                    isHelpful
                        ? 'bg-teal-100 text-teal-700 hover:bg-teal-200'
                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                ]"
            >
                <ThumbsUp :size="14" />
                <span>Helpful</span>
            </button>

            <button
                @click="handleReply"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors"
            >
                <MessageCircle :size="14" />
                <span>Reply</span>
            </button>

            <button
                @click="toggleSaved"
                :class="[
                    'flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors',
                    isSaved
                        ? 'bg-amber-100 text-amber-700 hover:bg-amber-200'
                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                ]"
            >
                <Bookmark :size="14" :fill="isSaved ? 'currentColor' : 'none'" />
                <span>Save</span>
            </button>

            <button
                @click="handleShare"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors"
            >
                <Share2 :size="14" />
                <span>Share</span>
            </button>
        </div>
    </div>
</template>
