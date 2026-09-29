<?php

namespace App\Http\Controllers;

use App\Models\Attempt;
use App\Models\Tryout;
use App\Services\AttemptService;
use App\Services\TryoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class TryoutController extends Controller
{
    public function __construct(
        private readonly AttemptService $attemptService,
        private readonly TryoutService $tryoutService,
    ) {}

    /**
     * Daftar tryout untuk peserta: yang sedang published (baik masih dalam
     * jendela waktu, akan datang, maupun baru lewat) beserta status attempt
     * milik peserta yang login, supaya tidak ada tombol "Mulai" yang mati
     * untuk tryout yang sudah pernah diikuti.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $tryouts = Tryout::query()
            ->whereIn('status', ['published', 'closed'])
            ->orderBy('starts_at', 'desc')
            ->get()
            ->map(function (Tryout $tryout) use ($user) {
                $tryout = $this->tryoutService->closeIfPassed($tryout);
                $tryout->myAttempt = Attempt::where('tryout_id', $tryout->id)
                    ->where('user_id', $user->id)
                    ->first();

                return $tryout;
            });

        return view('tryout.index', ['tryouts' => $tryouts]);
    }

    /**
     * Memulai tryout. Semua validasi (jendela waktu, status, sudah pernah
     * ikut atau belum) dan pengacakan soal dilakukan di server lewat
     * AttemptService, tidak pernah mempercayai input dari klien.
     */
    public function mulai(Request $request, Tryout $tryout): RedirectResponse
    {
        try {
            $this->attemptService->start($tryout, $request->user());
        } catch (RuntimeException $e) {
            return redirect()->route('tryout.index')->with('error', $e->getMessage());
        }

        return redirect()->route('tryout.kerjakan', $tryout);
    }

    /**
     * Halaman hasil milik peserta sendiri untuk satu tryout.
     */
    public function hasil(Request $request, Tryout $tryout): View
    {
        $attempt = Attempt::where('tryout_id', $tryout->id)
            ->where('user_id', $request->user()->id)
            ->with('attemptQuestions.question')
            ->firstOrFail();

        if ($attempt->isOngoing()) {
            $attempt = $this->attemptService->forceSubmitIfExpired($attempt);
        }

        if ($attempt->isOngoing()) {
            return redirect()->route('tryout.kerjakan', $tryout);
        }

        return view('tryout.hasil', ['tryout' => $tryout, 'attempt' => $attempt]);
    }
}
