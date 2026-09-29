<?php

namespace App\Services;

use App\Models\Tryout;
use Illuminate\Support\Carbon;

/**
 * Handles flipping a tryout's status to "closed" once its ends_at has
 * passed, either for one tryout (checked on access, e.g. when a
 * participant opens the ranking page) or in bulk (called from the
 * scheduled command).
 */
class TryoutService
{
    public function closeIfPassed(Tryout $tryout, ?Carbon $now = null): Tryout
    {
        $now ??= now();

        if ($tryout->status === 'published' && $tryout->hasSchedulePassed($now)) {
            $tryout->status = 'closed';
            $tryout->save();
        }

        return $tryout;
    }

    /**
     * Close every published tryout whose ends_at has passed. Returns how
     * many were closed.
     */
    public function closeAllPassed(?Carbon $now = null): int
    {
        $now ??= now();

        return Tryout::where('status', 'published')
            ->where('ends_at', '<', $now)
            ->update(['status' => 'closed']);
    }
}
