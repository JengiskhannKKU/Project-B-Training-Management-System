<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\Enrollment;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Seed session reviews from completed enrollments.
     */
    public function run(): void
    {
        // Find completed enrollments
        $completedEnrollments = Enrollment::where('status', 'completed')
            ->whereNotNull('completed_at')
            ->with(['session', 'session.course', 'user'])
            ->get();

        if ($completedEnrollments->isEmpty()) {
            $this->command->warn('No completed enrollments found for review seeding.');
            return;
        }

        $reviewCount = 0;

        foreach ($completedEnrollments as $enrollment) {
            // 70% of completed enrollments get reviews
            if (rand(0, 100) < 70) {
                // Generate rating (mostly positive: 60% = 5 stars, 25% = 4 stars, 15% = 3 or less)
                $rating = $this->generateRating();
                $comment = $this->generateComment($rating);

                Review::updateOrCreate(
                    [
                        'enrollment_id' => $enrollment->id,
                    ],
                    [
                        'user_id' => $enrollment->user_id,
                        'session_id' => $enrollment->session_id,
                        'course_id' => $enrollment->session->course_id,
                        'rating' => $rating,
                        'comment' => $comment,
                    ]
                );

                $reviewCount++;
            }
        }

        $this->command->info('Session reviews seeded: ' . $reviewCount);
    }

    /**
     * Generate weighted random rating (skewed toward positive)
     */
    private function generateRating(): int
    {
        $rand = rand(0, 100);

        if ($rand < 60) {
            return 5; // 60% chance
        } elseif ($rand < 85) {
            return 4; // 25% chance
        } elseif ($rand < 95) {
            return 3; // 10% chance
        } elseif ($rand < 98) {
            return 2; // 3% chance
        } else {
            return 1; // 2% chance
        }
    }

    /**
     * Generate comment based on rating
     */
    private function generateComment(int $rating): string
    {
        $comments = [
            5 => [
                'Excellent course! The trainer was very knowledgeable and engaging.',
                'Really enjoyed this training. Learned a lot of practical skills.',
                'Outstanding content and delivery. Highly recommend!',
                'The best training session I have attended. Very comprehensive.',
                'Great experience overall. The materials were well-organized.',
                'Fantastic course! The hands-on exercises were very helpful.',
                'Exceeded my expectations. Looking forward to more courses like this.',
            ],
            4 => [
                'Very good course. Some topics could be covered in more depth.',
                'Solid training with practical examples. Enjoyed it.',
                'Good content and delivery. Would have liked more time for Q&A.',
                'Informative and well-structured. Minor issues with pacing.',
                'Good course overall. The trainer was helpful and responsive.',
                'Nice training session. Would appreciate more interactive activities.',
            ],
            3 => [
                'Decent course but could be improved. Some parts were too basic.',
                'Average experience. The content was okay but delivery could be better.',
                'It was fine. Some topics were interesting, others not so much.',
                'Acceptable training. Expected more advanced content.',
                'The course covered the basics well but lacked depth.',
            ],
            2 => [
                'Not very satisfied. The content was outdated.',
                'Below expectations. The materials need updating.',
                'Disappointed with the course. Too much theory, not enough practice.',
                'The session did not meet my learning goals.',
            ],
            1 => [
                'Very disappointing. The course was poorly organized.',
                'Not recommended. The content was not relevant to my needs.',
                'Waste of time. Expected much better quality.',
            ],
        ];

        $ratingComments = $comments[$rating] ?? $comments[3];
        return $ratingComments[array_rand($ratingComments)];
    }
}
