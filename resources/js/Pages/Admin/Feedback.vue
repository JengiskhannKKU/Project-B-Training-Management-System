<script setup>
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import OverallRating from '@/Components/OverallRating.vue';
import SentimentOverview from '@/Components/SentimentOverview.vue';
import CommentsPanel from '@/Components/CommentsPanel.vue';
import ExportModal from '@/Components/ExportModal.vue';
import { Download } from 'lucide-vue-next';
import { mockFeedbacks, calculateFeedbackStats } from '@/mockData/feedbackData.js';
import jsPDF from 'jspdf';
import 'jspdf-autotable';

// Admin sees all feedbacks (no filtering by trainer)
const allFeedbacks = mockFeedbacks;

// Calculate statistics
const stats = computed(() => calculateFeedbackStats(allFeedbacks));

// Export modal state
const showExportModal = ref(false);

// Export feedback data to CSV
const exportToCSV = () => {
    // Convert feedback data to CSV format
    const headers = ['Reviewer', 'Course', 'Rating', 'Sentiment', 'Review', 'Date'];
    const csvData = allFeedbacks.map(review => [
        review.reviewerName,
        review.courseName,
        review.rating,
        review.sentiment,
        `"${review.reviewText.replace(/"/g, '""')}"`, // Escape quotes
        review.date
    ]);

    const csv = [
        headers.join(','),
        ...csvData.map(row => row.join(','))
    ].join('\n');

    // Create and download file
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `feedback-export-${new Date().toISOString().split('T')[0]}.csv`;
    link.click();
    window.URL.revokeObjectURL(url);
    showExportModal.value = false;
};

// Export feedback data to PDF
const exportToPDF = () => {
    try {
        const doc = new jsPDF();

        // Add title
        doc.setFontSize(16);
        doc.text('Feedback & Reviews Report', 14, 20);

        // Add generation date
        doc.setFontSize(10);
        doc.text(`Generated: ${new Date().toLocaleDateString()}`, 14, 28);

        // Prepare table data with null-safe values
        const headers = [['Reviewer', 'Course', 'Rating', 'Sentiment', 'Date']];
        const data = allFeedbacks.map(review => [
            review.reviewerName ?? '',
            review.courseName ?? '',
            review.rating ?? '',
            review.sentiment ?? '',
            review.date ?? ''
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
        doc.save(`feedback-export-${new Date().toISOString().split('T')[0]}.pdf`);
    } catch (error) {
        console.error('Error generating PDF:', error);
        alert('Failed to generate PDF. Please try again.');
    } finally {
        showExportModal.value = false;
    }
};
</script>

<template>
    <Head title="Feedback & Reviews" />
    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Feedback & Reviews</h1>
                <p class="mt-2 text-sm text-gray-600">Monitor and analyze student feedback across all courses</p>
            </div>

            <!-- Two Column Layout -->
            <div class="bg-white border-[#DFE5EF] rounded-xl p-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:h-[800px]">
                    <!-- Left Column: Analytics Panel (1/3 width) -->
                    <div class="lg:col-span-1 space-y-6 flex flex-col">
                        <!-- Overall Rating Component -->
                        <OverallRating
                            :average-rating="stats.averageRating"
                            :total-reviews="stats.totalReviews"
                            :distribution-percentages="stats.distributionPercentages"
                        />

                        <!-- Sentiment Overview Component -->
                        <SentimentOverview
                            :sentiment-percentages="stats.sentimentPercentages"
                        />
                    </div>

                    <!-- Right Column: Comments Reader Panel (2/3 width) -->
                    <div class="lg:col-span-2 flex flex-col min-h-0">
                        <CommentsPanel :reviews="allFeedbacks" />
                    </div>
                </div>

                <!-- Export Button Row -->
                <div class="flex justify-end mt-6 pt-6">
                    <button
                        @click="showExportModal = true"
                        class="flex items-center gap-2 px-4 py-2.5 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors shadow-lg hover:shadow-xl font-medium text-sm"
                    >
                        <Download :size="18" />
                        <span>Export Data</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Export Modal -->
        <ExportModal
            v-if="showExportModal"
            @close="showExportModal = false"
            @exportCSV="exportToCSV"
            @exportPDF="exportToPDF"
        />
    </AdminLayout>
</template>
