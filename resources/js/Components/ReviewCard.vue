<script setup>
import { Star, ThumbsUp, MessageCircle, Bookmark, Share2 } from 'lucide-vue-next';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    review: {
        type: Object,
        required: true
    }
});

const formatDate = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffTime = Math.abs(now - date);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays === 0) return 'Today';
    if (diffDays === 1) return 'Yesterday';
    if (diffDays < 7) return `${diffDays} days ago`;
    if (diffDays < 30) return `${Math.floor(diffDays / 7)} weeks ago`;
    if (diffDays < 365) return `${Math.floor(diffDays / 30)} months ago`;
    return `${Math.floor(diffDays / 365)} years ago`;
};
</script>

<template>
    <div class="bg-white rounded-xl shadow-sm border border-[#dfe5ef] p-5 hover:shadow-md transition-shadow">
        <!-- Header: User Info and Rating -->
        <div class="flex items-start justify-between mb-3">
            <div class="flex items-center gap-3">
                <!-- User Avatar -->
                <div class="w-10 h-10 bg-gradient-to-r from-[#2f837d] to-[#4a9d96] rounded-full flex items-center justify-center text-white font-semibold text-sm">
                    {{ review.userName.charAt(0).toUpperCase() }}
                </div>

                <!-- User Info -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">{{ review.userName }}</h3>
                    <p class="text-xs text-gray-500">{{ formatDate(review.date) }}</p>
                </div>
            </div>

            <!-- Star Rating -->
            <div class="flex items-center gap-1">
                <Star
                    v-for="i in 5"
                    :key="i"
                    :size="16"
                    :class="i <= review.rating ? 'text-yellow-400' : 'text-gray-300'"
                    :fill="i <= review.rating ? 'currentColor' : 'none'"
                />
            </div>
        </div>

        <!-- Course Name -->
        <div class="mb-2">
            <span class="text-xs font-medium text-[#2f837d] bg-[#2f837d]/10 px-2 py-1 rounded">
                {{ review.courseName }}
            </span>
        </div>

        <!-- Review Comment -->
        <p class="text-sm text-gray-700 leading-relaxed mb-4">
            {{ review.comment }}
        </p>

        <!-- Footer: Actions -->
        <div class="flex items-center gap-3 pt-3 border-t border-gray-100">
            <SecondaryButton class="!py-1.5 !px-3 !text-xs !normal-case !tracking-normal">
                <ThumbsUp :size="14" class="mr-1.5" />
                Helpful ({{ review.helpfulCount || 0 }})
            </SecondaryButton>
            <SecondaryButton class="!py-1.5 !px-3 !text-xs !normal-case !tracking-normal">
                <MessageCircle :size="14" class="mr-1.5" />
                Reply
            </SecondaryButton>
            <SecondaryButton class="!py-1.5 !px-3 !text-xs !normal-case !tracking-normal">
                <Bookmark :size="14" class="mr-1.5" />
                Save
            </SecondaryButton>
            <SecondaryButton class="!py-1.5 !px-3 !text-xs !normal-case !tracking-normal">
                <Share2 :size="14" class="mr-1.5" />
                Share
            </SecondaryButton>
        </div>
    </div>
</template>
