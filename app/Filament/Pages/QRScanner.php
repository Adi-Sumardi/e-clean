<?php

namespace App\Filament\Pages;

use App\Models\JadwalKebersihan;
use App\Models\Lokasi;
use App\Services\QRCodeService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;

class QRScanner extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-qr-code';

    protected string $view = 'filament.pages.qr-scanner';

    protected static ?string $title = 'Scan QR Code Lokasi';

    protected static ?string $navigationLabel = 'Scan QR Code';

    // Removed navigationGroup - make it a direct menu item
    // protected static string | \UnitEnum | null $navigationGroup = 'Tools';

    protected static ?int $navigationSort = 50;

    public ?array $scannedData = null;
    public ?Lokasi $scannedLokasi = null;
    public bool $hasJadwal = false;
    public ?JadwalKebersihan $jadwal = null;

    public static function canView(): bool
    {
        // Only petugas can access QR Scanner
        return Auth::user()->hasRole('petugas');
    }

    public static function shouldRegisterNavigation(): bool
    {
        // Hide from navigation for non-petugas users
        return Auth::user()->hasRole('petugas');
    }

    #[On('qr-scanned')]
    public function handleQRScanned($qrData): void
    {
        $qrCodeService = new QRCodeService();
        $decoded = $qrCodeService->decodeQRData($qrData);

        if (!$decoded) {
            Notification::make()
                ->title('QR Code Tidak Valid')
                ->body('QR Code yang dipindai bukan QR Code lokasi Clean Service System')
                ->danger()
                ->send();
            return;
        }

        // Get lokasi data
        $this->scannedLokasi = Lokasi::find($decoded['id']);

        if (!$this->scannedLokasi) {
            Notification::make()
                ->title('Lokasi Tidak Ditemukan')
                ->body('Lokasi dengan ID ' . $decoded['id'] . ' tidak ditemukan dalam database')
                ->warning()
                ->send();
            return;
        }

        // Check if petugas has jadwal for this location today
        $today = Carbon::today();
        $this->jadwal = JadwalKebersihan::where('petugas_id', Auth::id())
            ->where('lokasi_id', $this->scannedLokasi->id)
            ->whereDate('tanggal', $today)
            ->first();

        $this->hasJadwal = $this->jadwal !== null;
        $this->scannedData = $decoded;

        if ($this->hasJadwal) {
            Notification::make()
                ->title('QR Code Berhasil Dipindai!')
                ->body('Lokasi: ' . $this->scannedLokasi->nama_lokasi . ' - Shift: ' . ucfirst($this->jadwal->shift))
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Lokasi Tidak Terdaftar')
                ->body('Anda tidak memiliki jadwal untuk lokasi ini hari ini. Silakan hubungi supervisor Anda.')
                ->warning()
                ->duration(10000)
                ->send();
        }
    }

    public function resetScanner(): void
    {
        $this->scannedData = null;
        $this->scannedLokasi = null;
        $this->hasJadwal = false;
        $this->jadwal = null;
    }

    public function createReport(): void
    {
        if (!$this->scannedLokasi || !$this->hasJadwal) {
            Notification::make()
                ->title('Tidak Dapat Membuat Laporan')
                ->body('Anda tidak memiliki akses untuk membuat laporan di lokasi ini.')
                ->danger()
                ->send();
            return;
        }

        // Redirect to create activity report with lokasi and jadwal pre-filled
        $this->redirect(route('filament.admin.resources.activity-reports.activity-reports.create', [
            'lokasi_id' => $this->scannedLokasi->id,
            'jadwal_id' => $this->jadwal->id
        ]));
    }
}
