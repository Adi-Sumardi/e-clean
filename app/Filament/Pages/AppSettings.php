<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class AppSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.app-settings';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Pengaturan Aplikasi';

    protected static ?string $title = 'Pengaturan Aplikasi';

    protected static ?int $navigationSort = 100;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'reporting_tolerance_minutes' => Setting::get('reporting_tolerance_minutes', 10),
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Pengaturan Laporan Kegiatan')
                    ->description('Konfigurasi toleransi waktu pelaporan untuk petugas')
                    ->schema([
                        TextInput::make('reporting_tolerance_minutes')
                            ->label('Toleransi Keterlambatan (menit)')
                            ->helperText('Jumlah menit setelah jam_selesai jadwal yang masih dianggap "terlambat". Setelah melebihi toleransi ini, laporan akan otomatis di-generate oleh sistem dengan status "Tidak Lapor".')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(120)
                            ->default(10)
                            ->suffix('menit')
                            ->required(),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::set(
            'reporting_tolerance_minutes',
            (int) $data['reporting_tolerance_minutes'],
            'integer',
            'reporting'
        );

        Notification::make()
            ->title('Pengaturan Berhasil Disimpan')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Pengaturan')
                ->submit('save'),
        ];
    }

    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(['admin', 'super_admin', 'supervisor']);
    }
}
