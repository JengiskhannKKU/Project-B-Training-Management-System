// Mock feedback data for Admin/Trainer Feedback Dashboard

export const mockFeedbacks = [
    {
        id: 1,
        reviewerName: 'Alice Cooper',
        reviewerAvatar: null, // Will use initials
        courseName: 'Advanced Laravel Development',
        rating: 5,
        reviewText: 'Excellent course! The instructor explained complex concepts very clearly. The hands-on projects were particularly helpful in solidifying my understanding. I would highly recommend this to anyone looking to master Laravel.',
        date: '2025-12-26',
        relativeTime: '2 days ago',
        sentiment: 'positive',
        trainerId: 1
    },
    {
        id: 2,
        reviewerName: 'Bob Martin',
        reviewerAvatar: null,
        courseName: 'Vue.js Masterclass',
        rating: 4,
        reviewText: 'Great course overall, but some topics could use more examples. The pacing was good and the instructor was knowledgeable.',
        date: '2025-12-25',
        relativeTime: '3 days ago',
        sentiment: 'positive',
        trainerId: 2
    },
    {
        id: 3,
        reviewerName: 'Charlie Davis',
        reviewerAvatar: null,
        courseName: 'Digital Marketing Strategy',
        rating: 3,
        reviewText: 'The content was good, but the pace was a bit too fast. I had to rewatch several sections to fully grasp the concepts.',
        date: '2025-12-24',
        relativeTime: '4 days ago',
        sentiment: 'neutral',
        trainerId: 1
    },
    {
        id: 4,
        reviewerName: 'Diana Prince',
        reviewerAvatar: null,
        courseName: 'UI/UX Design Principles',
        rating: 5,
        reviewText: 'Outstanding! I learned so much from this course. The design exercises were practical and immediately applicable to my work.',
        date: '2025-12-23',
        relativeTime: '5 days ago',
        sentiment: 'positive',
        trainerId: 2
    },
    {
        id: 5,
        reviewerName: 'Ethan Hunt',
        reviewerAvatar: null,
        courseName: 'React Native Development',
        rating: 2,
        reviewText: 'Unfortunately, this course did not meet my expectations. The examples were outdated and the explanations were unclear.',
        date: '2025-12-22',
        relativeTime: '6 days ago',
        sentiment: 'negative',
        trainerId: 1
    },
    {
        id: 6,
        reviewerName: 'Fiona Green',
        reviewerAvatar: null,
        courseName: 'Python for Data Science',
        rating: 5,
        reviewText: 'Amazing course! The instructor made complex data science concepts easy to understand. Highly recommend!',
        date: '2025-12-21',
        relativeTime: '1 week ago',
        sentiment: 'positive',
        trainerId: 2
    },
    {
        id: 7,
        reviewerName: 'George Wilson',
        reviewerAvatar: null,
        courseName: 'Advanced Laravel Development',
        rating: 4,
        reviewText: 'Very comprehensive course. Covered all the advanced topics I needed. Would have liked more real-world examples.',
        date: '2025-12-20',
        relativeTime: '1 week ago',
        sentiment: 'positive',
        trainerId: 1
    },
    {
        id: 8,
        reviewerName: 'Hannah Lee',
        reviewerAvatar: null,
        courseName: 'Digital Marketing Strategy',
        rating: 5,
        reviewText: 'Best marketing course I have taken! The strategies taught are practical and results-driven.',
        date: '2025-12-19',
        relativeTime: '1 week ago',
        sentiment: 'positive',
        trainerId: 1
    },
    {
        id: 9,
        reviewerName: 'Ian Malcolm',
        reviewerAvatar: null,
        courseName: 'Vue.js Masterclass',
        rating: 3,
        reviewText: 'Decent course but nothing extraordinary. Some sections felt rushed while others were too slow.',
        date: '2025-12-18',
        relativeTime: '1 week ago',
        sentiment: 'neutral',
        trainerId: 2
    },
    {
        id: 10,
        reviewerName: 'Julia Roberts',
        reviewerAvatar: null,
        courseName: 'UI/UX Design Principles',
        rating: 4,
        reviewText: 'Really enjoyed this course. The practical exercises helped me improve my design skills significantly.',
        date: '2025-12-17',
        relativeTime: '1 week ago',
        sentiment: 'positive',
        trainerId: 2
    },
    {
        id: 11,
        reviewerName: 'Kevin Hart',
        reviewerAvatar: null,
        courseName: 'React Native Development',
        rating: 1,
        reviewText: 'Very disappointed. The course materials were poorly organized and the instructor seemed unprepared.',
        date: '2025-12-16',
        relativeTime: '2 weeks ago',
        sentiment: 'negative',
        trainerId: 1
    },
    {
        id: 12,
        reviewerName: 'Laura Palmer',
        reviewerAvatar: null,
        courseName: 'Python for Data Science',
        rating: 5,
        reviewText: 'Excellent course! Clear explanations, great examples, and very engaging instructor.',
        date: '2025-12-15',
        relativeTime: '2 weeks ago',
        sentiment: 'positive',
        trainerId: 2
    },
    {
        id: 13,
        reviewerName: 'Michael Scott',
        reviewerAvatar: null,
        courseName: 'Advanced Laravel Development',
        rating: 4,
        reviewText: 'Solid course with good content. The projects were challenging and educational.',
        date: '2025-12-14',
        relativeTime: '2 weeks ago',
        sentiment: 'positive',
        trainerId: 1
    },
    {
        id: 14,
        reviewerName: 'Nina Simone',
        reviewerAvatar: null,
        courseName: 'Digital Marketing Strategy',
        rating: 2,
        reviewText: 'Expected more from this course. The content felt generic and lacked depth.',
        date: '2025-12-13',
        relativeTime: '2 weeks ago',
        sentiment: 'negative',
        trainerId: 1
    },
    {
        id: 15,
        reviewerName: 'Oscar Wilde',
        reviewerAvatar: null,
        courseName: 'Vue.js Masterclass',
        rating: 5,
        reviewText: 'Fantastic course! I went from beginner to building full applications confidently.',
        date: '2025-12-12',
        relativeTime: '2 weeks ago',
        sentiment: 'positive',
        trainerId: 2
    }
];

// Calculate statistics from feedback data
export const calculateFeedbackStats = (feedbacks) => {
    const totalReviews = feedbacks.length;

    // Calculate average rating
    const totalRating = feedbacks.reduce((sum, feedback) => sum + feedback.rating, 0);
    const averageRating = totalReviews > 0 ? (totalRating / totalReviews).toFixed(1) : 0;

    // Calculate rating distribution
    const distribution = {
        5: 0,
        4: 0,
        3: 0,
        2: 0,
        1: 0
    };

    feedbacks.forEach(feedback => {
        distribution[feedback.rating]++;
    });

    // Calculate percentages
    const distributionPercentages = {};
    Object.keys(distribution).forEach(rating => {
        distributionPercentages[rating] = totalReviews > 0
            ? Math.round((distribution[rating] / totalReviews) * 100)
            : 0;
    });

    // Calculate sentiment percentages
    const sentimentCounts = {
        positive: 0,
        neutral: 0,
        negative: 0
    };

    feedbacks.forEach(feedback => {
        sentimentCounts[feedback.sentiment]++;
    });

    const sentimentPercentages = {
        positive: totalReviews > 0 ? Math.round((sentimentCounts.positive / totalReviews) * 100) : 0,
        neutral: totalReviews > 0 ? Math.round((sentimentCounts.neutral / totalReviews) * 100) : 0,
        negative: totalReviews > 0 ? Math.round((sentimentCounts.negative / totalReviews) * 100) : 0
    };

    return {
        totalReviews,
        averageRating: parseFloat(averageRating),
        distribution,
        distributionPercentages,
        sentimentPercentages
    };
};

// Filter feedbacks by trainer ID
export const filterFeedbacksByTrainer = (feedbacks, trainerId) => {
    return feedbacks.filter(feedback => feedback.trainerId === trainerId);
};
