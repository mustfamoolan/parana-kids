<?php

namespace App\Console\Commands;

use App\Models\ProductLink;
use Illuminate\Console\Command;
use Carbon\Carbon;

class DeleteExpiredProductLinks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product-links:delete-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete product links that were created more than 2 hours ago';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting expired product links deletion process...');

        // البحث عن الروابط التي مضى عليها ساعتان أو أكثر
        $expiredLinks = ProductLink::where('created_at', '<=', Carbon::now()->subHours(2))->get();

        if ($expiredLinks->isEmpty()) {
            $this->info('No expired product links found.');
            return;
        }

        $this->info("Found {$expiredLinks->count()} expired product links.");

        $deletedCount = 0;

        foreach ($expiredLinks as $link) {
            $link->delete();
            $deletedCount++;
            $this->line("Deleted product link: Token {$link->token} (ID: {$link->id})");
        }

        $this->info("Successfully deleted {$deletedCount} expired product links.");
    }
}

