<?php

namespace Database\Seeders;

use App\Models\Attempt;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Tryout;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with demo accounts and a set of
     * genuine (if simple) sample exam questions across three subjects,
     * plus two demo tryouts: one currently open, one already closed with
     * finished attempts so the ranking page has something to show right
     * after seeding.
     *
     * Semua akun di bawah ini adalah akun demo, bukan orang sungguhan.
     */
    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin Demo',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $peserta1 = User::factory()->create([
            'name' => 'Budi Santoso (Demo)',
            'email' => 'peserta@example.com',
            'password' => bcrypt('password'),
            'role' => 'peserta',
        ]);

        $peserta2 = User::factory()->create([
            'name' => 'Siti Aminah (Demo)',
            'email' => 'peserta2@example.com',
            'password' => bcrypt('password'),
            'role' => 'peserta',
        ]);

        $matematika = Subject::create(['name' => 'Matematika Dasar']);
        $bahasaIndonesia = Subject::create(['name' => 'Bahasa Indonesia']);
        $wawasanUmum = Subject::create(['name' => 'Wawasan Umum']);

        $matematikaQuestions = $this->seedQuestions($matematika, $this->matematikaSoal());
        $bahasaQuestions = $this->seedQuestions($bahasaIndonesia, $this->bahasaIndonesiaSoal());
        $wawasanQuestions = $this->seedQuestions($wawasanUmum, $this->wawasanUmumSoal());

        // Tryout 1: sedang dibuka, campuran ketiga mapel, untuk dicoba langsung.
        $tryoutTerbuka = Tryout::create([
            'title' => 'Tryout Simulasi Dasar (Campuran)',
            'subject_id' => null,
            'duration_minutes' => 30,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(7),
            'question_count' => 15,
            'status' => 'published',
        ]);

        $allQuestionIds = $matematikaQuestions->pluck('id')
            ->merge($bahasaQuestions->pluck('id'))
            ->merge($wawasanQuestions->pluck('id'));

        $tryoutTerbuka->questionPool()->attach($allQuestionIds);

        // Tryout 2: sudah ditutup, khusus Matematika, dengan attempt yang
        // sudah selesai untuk kedua peserta demo, supaya halaman peringkat
        // langsung ada isinya begitu selesai seeding.
        $tryoutTertutup = Tryout::create([
            'title' => 'Tryout Matematika Dasar (Contoh, Sudah Ditutup)',
            'subject_id' => $matematika->id,
            'duration_minutes' => 20,
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->subDay(),
            'question_count' => 8,
            'status' => 'closed',
        ]);

        $tryoutTertutup->questionPool()->attach($matematikaQuestions->pluck('id'));

        $this->seedFinishedAttempt($tryoutTertutup, $peserta1, $matematikaQuestions, correctRatio: 0.75, minutesTaken: 12);
        $this->seedFinishedAttempt($tryoutTertutup, $peserta2, $matematikaQuestions, correctRatio: 0.5, minutesTaken: 18);
    }

    /**
     * @param  array<int, array{body: string, options: array<int, string>, correct: int}>  $items
     * @return \Illuminate\Support\Collection<int, Question>
     */
    private function seedQuestions(Subject $subject, array $items): \Illuminate\Support\Collection
    {
        return collect($items)->map(function (array $item) use ($subject) {
            $question = Question::create([
                'subject_id' => $subject->id,
                'body' => $item['body'],
                'difficulty' => $item['difficulty'] ?? 'medium',
                'points' => 1,
            ]);

            foreach ($item['options'] as $index => $optionBody) {
                $question->options()->create([
                    'body' => $optionBody,
                    'is_correct' => $index === $item['correct'],
                ]);
            }

            return $question;
        });
    }

    /**
     * Creates a finished (submitted) attempt with plausible answers, so
     * demo data for the ranking page is real graded data, not fabricated
     * numbers: the score is computed the same way AttemptService does it.
     *
     * @param  \Illuminate\Support\Collection<int, Question>  $questionPool
     */
    private function seedFinishedAttempt(Tryout $tryout, User $user, \Illuminate\Support\Collection $questionPool, float $correctRatio, int $minutesTaken): void
    {
        $questions = $questionPool->shuffle()->take($tryout->question_count)->values();
        $startedAt = $tryout->ends_at->copy()->subDays(2);

        $attempt = Attempt::create([
            'tryout_id' => $tryout->id,
            'user_id' => $user->id,
            'started_at' => $startedAt,
            'status' => 'ongoing',
        ]);

        $earned = 0;
        $total = 0;
        $correctTarget = (int) round($questions->count() * $correctRatio);

        foreach ($questions as $index => $question) {
            $options = $question->options()->get();
            $correctOption = $options->firstWhere('is_correct', true);
            $shouldAnswerCorrectly = $index < $correctTarget;
            $selected = $shouldAnswerCorrectly ? $correctOption : $options->firstWhere('is_correct', false);

            $attempt->attemptQuestions()->create([
                'question_id' => $question->id,
                'display_order' => $index + 1,
                'shuffled_option_order' => $options->pluck('id')->all(),
                'selected_option_id' => $selected?->id,
                'is_correct' => $shouldAnswerCorrectly,
                'answered_at' => $startedAt->copy()->addMinutes($index),
            ]);

            $total += $question->points;

            if ($shouldAnswerCorrectly) {
                $earned += $question->points;
            }
        }

        $attempt->update([
            'status' => 'submitted',
            'score' => $total > 0 ? round(($earned / $total) * 100, 2) : 0,
            'submitted_at' => $startedAt->copy()->addMinutes($minutesTaken),
        ]);
    }

    /**
     * @return array<int, array{body: string, options: array<int, string>, correct: int, difficulty?: string}>
     */
    private function matematikaSoal(): array
    {
        return [
            ['body' => 'Hasil dari 15 + 27 x 3 adalah...', 'options' => ['96', '126', '78', '108'], 'correct' => 0, 'difficulty' => 'easy'],
            ['body' => 'Jika x + 5 = 12, maka nilai x adalah...', 'options' => ['7', '17', '5', '12'], 'correct' => 0, 'difficulty' => 'easy'],
            ['body' => 'Hasil dari 8² − 3² adalah...', 'options' => ['55', '45', '61', '49'], 'correct' => 0, 'difficulty' => 'medium'],
            ['body' => 'Keliling persegi dengan panjang sisi 9 cm adalah...', 'options' => ['36 cm', '81 cm', '18 cm', '45 cm'], 'correct' => 0, 'difficulty' => 'easy'],
            ['body' => 'Hasil dari 3/4 + 1/2 adalah...', 'options' => ['5/4', '4/4', '3/2', '1/4'], 'correct' => 0, 'difficulty' => 'medium'],
            ['body' => '20% dari 150 adalah...', 'options' => ['30', '15', '45', '20'], 'correct' => 0, 'difficulty' => 'easy'],
            ['body' => 'Sebuah mobil menempuh 180 km dalam 3 jam. Kecepatan rata-ratanya adalah...', 'options' => ['60 km/jam', '45 km/jam', '90 km/jam', '30 km/jam'], 'correct' => 0, 'difficulty' => 'medium'],
            ['body' => 'Akar kuadrat dari 144 adalah...', 'options' => ['12', '14', '10', '16'], 'correct' => 0, 'difficulty' => 'easy'],
            ['body' => 'Sebuah segitiga siku-siku memiliki sisi tegak 3 cm dan 4 cm. Panjang sisi miringnya adalah...', 'options' => ['5 cm', '6 cm', '7 cm', '4 cm'], 'correct' => 0, 'difficulty' => 'hard'],
            ['body' => 'Median dari data 3, 7, 7, 2, 9 adalah...', 'options' => ['7', '3', '9', '2'], 'correct' => 0, 'difficulty' => 'medium'],
        ];
    }

    /**
     * @return array<int, array{body: string, options: array<int, string>, correct: int, difficulty?: string}>
     */
    private function bahasaIndonesiaSoal(): array
    {
        return [
            ['body' => 'Penulisan kata baku yang benar menurut KBBI adalah...', 'options' => ['jadwal', 'jadual', 'jadwl', 'jaduwal'], 'correct' => 0, 'difficulty' => 'easy'],
            ['body' => 'Sinonim dari kata "cepat" adalah...', 'options' => ['laju', 'lambat', 'diam', 'berat'], 'correct' => 0, 'difficulty' => 'easy'],
            ['body' => 'Antonim dari kata "besar" adalah...', 'options' => ['kecil', 'luas', 'tinggi', 'panjang'], 'correct' => 0, 'difficulty' => 'easy'],
            ['body' => 'Kalimat berikut yang menggunakan huruf kapital dengan benar adalah...', 'options' => ['Saya pergi ke Jakarta minggu depan.', 'saya pergi ke jakarta minggu depan.', 'Saya pergi ke jakarta minggu depan.', 'saya Pergi ke Jakarta minggu depan.'], 'correct' => 0, 'difficulty' => 'medium'],
            ['body' => 'Kata dasar dari kata "mempelajari" adalah...', 'options' => ['ajar', 'pelajar', 'belajar', 'pelajaran'], 'correct' => 0, 'difficulty' => 'medium'],
            ['body' => 'Paragraf yang gagasan utamanya terletak di awal paragraf disebut paragraf...', 'options' => ['deduktif', 'induktif', 'naratif', 'deskriptif'], 'correct' => 0, 'difficulty' => 'medium'],
            ['body' => 'Kata "seksama" pada kalimat "Ia membaca surat itu dengan seksama" bermakna...', 'options' => ['teliti', 'cepat', 'malas', 'keras'], 'correct' => 0, 'difficulty' => 'medium'],
            ['body' => 'Bentuk ulang "buku-buku" digunakan untuk menyatakan makna...', 'options' => ['banyak/jamak', 'tunggal', 'rusak', 'baru'], 'correct' => 0, 'difficulty' => 'easy'],
            ['body' => 'Kalimat yang termasuk kalimat tanya adalah...', 'options' => ['Kapan kamu berangkat ke sekolah?', 'Kamu berangkat ke sekolah pagi ini.', 'Berangkatlah ke sekolah sekarang.', 'Aku berangkat ke sekolah.'], 'correct' => 0, 'difficulty' => 'easy'],
            ['body' => 'Imbuhan yang tepat untuk kata dasar "ajar" agar bermakna "proses belajar mengajar" adalah "pem-...-an", sehingga menjadi...', 'options' => ['pembelajaran', 'pelajaran', 'ajaran', 'diajarkan'], 'correct' => 0, 'difficulty' => 'hard'],
        ];
    }

    /**
     * @return array<int, array{body: string, options: array<int, string>, correct: int, difficulty?: string}>
     */
    private function wawasanUmumSoal(): array
    {
        return [
            ['body' => 'Ibu kota Provinsi Jawa Timur adalah...', 'options' => ['Surabaya', 'Malang', 'Kediri', 'Madiun'], 'correct' => 0, 'difficulty' => 'easy'],
            ['body' => 'Proklamasi kemerdekaan Indonesia dibacakan pada tanggal...', 'options' => ['17 Agustus 1945', '20 Mei 1945', '28 Oktober 1945', '1 Juni 1945'], 'correct' => 0, 'difficulty' => 'easy'],
            ['body' => 'Pancasila sebagai dasar negara Indonesia terdiri dari... sila.', 'options' => ['5', '4', '6', '3'], 'correct' => 0, 'difficulty' => 'easy'],
            ['body' => 'Lagu kebangsaan Indonesia berjudul...', 'options' => ['Indonesia Raya', 'Garuda Pancasila', 'Bagimu Negeri', 'Maju Tak Gentar'], 'correct' => 0, 'difficulty' => 'easy'],
            ['body' => 'Gunung tertinggi di Indonesia adalah...', 'options' => ['Puncak Jaya', 'Gunung Kerinci', 'Gunung Semeru', 'Gunung Rinjani'], 'correct' => 0, 'difficulty' => 'medium'],
            ['body' => 'Mata uang resmi negara Indonesia adalah...', 'options' => ['Rupiah', 'Ringgit', 'Peso', 'Baht'], 'correct' => 0, 'difficulty' => 'easy'],
            ['body' => 'Presiden pertama Republik Indonesia adalah...', 'options' => ['Soekarno', 'Soeharto', 'B.J. Habibie', 'Mohammad Hatta'], 'correct' => 0, 'difficulty' => 'easy'],
            ['body' => 'Selat yang menghubungkan Pulau Jawa dan Pulau Sumatera adalah...', 'options' => ['Selat Sunda', 'Selat Malaka', 'Selat Bali', 'Selat Makassar'], 'correct' => 0, 'difficulty' => 'medium'],
            ['body' => 'Candi Borobudur terletak di provinsi...', 'options' => ['Jawa Tengah', 'Jawa Timur', 'Jawa Barat', 'Yogyakarta'], 'correct' => 0, 'difficulty' => 'medium'],
            ['body' => 'Organisasi yang menaungi kerja sama negara-negara Asia Tenggara disebut...', 'options' => ['ASEAN', 'PBB', 'APEC', 'OPEC'], 'correct' => 0, 'difficulty' => 'easy'],
        ];
    }
}
