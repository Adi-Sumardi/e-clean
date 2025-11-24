<?php

namespace App\Filament\Pages;

use App\Models\ActivityReport;
use App\Models\Penilaian;
use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;

class PetugasLeaderboard extends Page
{
    protected string $view = 'filament.pages.petugas-leaderboard';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $navigationLabel = 'Peringkat Petugas';

    // Remove navigationGroup - make it a top-level menu
    // protected static string | \UnitEnum | null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 50;

    // Enable realtime updates - auto refresh every 5 seconds
    protected static ?string $pollingInterval = '5s';

    public string $period = 'month';
    public string $startDate;
    public string $endDate;
    public string $lastUpdated;

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->endOfMonth()->format('Y-m-d');
        $this->lastUpdated = now()->format('H:i:s');
    }

    public function getPollingInterval(): ?string
    {
        return static::$pollingInterval;
    }

    public function updatedPeriod($value): void
    {
        if ($value !== 'custom') {
            match ($value) {
                'today' => [
                    $this->startDate = now()->startOfDay()->format('Y-m-d'),
                    $this->endDate = now()->endOfDay()->format('Y-m-d'),
                ],
                'week' => [
                    $this->startDate = now()->startOfWeek()->format('Y-m-d'),
                    $this->endDate = now()->endOfWeek()->format('Y-m-d'),
                ],
                'month' => [
                    $this->startDate = now()->startOfMonth()->format('Y-m-d'),
                    $this->endDate = now()->endOfMonth()->format('Y-m-d'),
                ],
                'year' => [
                    $this->startDate = now()->startOfYear()->format('Y-m-d'),
                    $this->endDate = now()->endOfYear()->format('Y-m-d'),
                ],
            };
        }
    }

    #[Computed]
    public function getLeaderboardData(): array
    {
        // Update timestamp untuk menunjukkan data di-refresh
        $this->lastUpdated = now()->format('H:i:s');

        $startDate = Carbon::parse($this->startDate);
        $endDate = Carbon::parse($this->endDate);

        // Get all petugas
        $allPetugas = User::whereHas('roles', function ($query) {
            $query->where('name', 'petugas');
        })->get();

        $leaderboard = [];

        foreach ($allPetugas as $petugas) {
            // Count approved reports
            $approvedReports = ActivityReport::where('petugas_id', $petugas->id)
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->where('status', 'approved')
                ->count();

            // Count all reports
            $totalReports = ActivityReport::where('petugas_id', $petugas->id)
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->count();

            // Average rating from reports
            $avgReportRating = ActivityReport::where('petugas_id', $petugas->id)
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->where('status', 'approved')
                ->whereNotNull('rating')
                ->avg('rating');

            // Calculate overall score
            // Formula: (approved_reports * 10) + (avg_report_rating * 20)
            $score = ($approvedReports * 10) + (($avgReportRating ?? 0) * 20);

            // Approval rate
            $approvalRate = $totalReports > 0 ? round(($approvedReports / $totalReports) * 100, 1) : 0;

            $leaderboard[] = [
                'petugas_id' => $petugas->id,
                'name' => $petugas->name,
                'email' => $petugas->email,
                'phone' => $petugas->phone,
                'approved_reports' => $approvedReports,
                'total_reports' => $totalReports,
                'approval_rate' => $approvalRate,
                'avg_report_rating' => $avgReportRating ? round($avgReportRating, 2) : null,
                'score' => round($score, 2),
            ];
        }

        // Sort by score descending
        usort($leaderboard, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // Add ranking
        foreach ($leaderboard as $index => &$data) {
            $data['rank'] = $index + 1;
        }

        return $leaderboard;
    }

    public static function canAccess(): bool
    {
        // Hide dari petugas
        return Auth::user()->hasAnyRole(['admin', 'super_admin', 'supervisor', 'pengurus']);
    }
}
