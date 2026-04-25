<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\DoubleBlindReviewService;
use Illuminate\Console\Command;

class PublishExpiredReviews extends Command
{
    protected $signature = 'rezi:publish-expired-reviews';

    protected $description = 'Publie les reviews bilatérales (guest+owner) dont la fenêtre 14j est écoulée';

    public function handle(DoubleBlindReviewService $service): int
    {
        $stats = $service->publishExpiredReviews();
        $this->info('Reviews publiées :');
        $this->line("  - Guest→Owner : {$stats['published_guest_reviews']}");
        $this->line("  - Owner→Guest : {$stats['published_owner_reviews']}");

        return self::SUCCESS;
    }
}
