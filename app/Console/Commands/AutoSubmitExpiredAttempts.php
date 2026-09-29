<?php

namespace App\Console\Commands;

use App\Models\Attempt;
use App\Services\AttemptService;
use App\Services\TryoutService;
use Illuminate\Console\Command;

/**
 * Runs every minute (see routes/console.php). Closes out any attempt that
 * is still "ongoing" but whose started_at + duration_minutes has already
 * passed, so a participant who simply closes the browser tab still ends
 * up with a graded, submitted attempt instead of one stuck open forever.
 * Also closes any published tryout whose ends_at has passed, which is
 * what unlocks the ranking page for it.
 *
 * In production this requires `php artisan schedule:work` (or a real cron
 * entry running `php artisan schedule:run` every minute) to actually run.
 */
class AutoSubmitExpiredAttempts extends Command
{
    protected $signature = 'attempts:auto-submit-expired';

    protected $description = 'Auto-submit ongoing attempts whose time has run out, and close tryouts past their schedule';

    public function handle(AttemptService $attemptService, TryoutService $tryoutService): int
    {
        $now = now();

        $expiredAttempts = Attempt::where('status', 'ongoing')
            ->with('tryout')
            ->get()
            ->filter(fn (Attempt $attempt) => $attempt->isTimeUp($now));

        foreach ($expiredAttempts as $attempt) {
            $attemptService->finalize($attempt, 'expired', $attempt->deadline());
            $this->info("Attempt #{$attempt->id} (tryout #{$attempt->tryout_id}, user #{$attempt->user_id}) auto-disubmit karena waktu habis.");
        }

        $closedCount = $tryoutService->closeAllPassed($now);

        if ($closedCount > 0) {
            $this->info("{$closedCount} tryout ditutup otomatis karena jadwal sudah lewat.");
        }

        return self::SUCCESS;
    }
}
