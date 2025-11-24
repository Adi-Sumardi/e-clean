<?php

namespace App\Filament\Widgets;

use App\Models\ActivityReport;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\ViewField;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class SupervisorPendingReportsWidget extends TableWidget implements HasForms, HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Laporan Menunggu Approval';

    protected string $view = 'filament.widgets.supervisor-pending-reports-widget';

    public static function canView(): bool
    {
        return Auth::user()->hasAnyRole(['supervisor', 'admin']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ActivityReport::query()
                    ->where('status', 'submitted')
                    ->with(['petugas', 'lokasi'])
                    ->orderBy('tanggal', 'desc')
            )
            ->columns([
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('petugas.name')
                    ->label('Petugas')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('lokasi.nama_lokasi')
                    ->label('Lokasi')
                    ->searchable()
                    ->wrap()
                    ->sortable(),

                TextColumn::make('kegiatan')
                    ->label('Kegiatan')
                    ->limit(50)
                    ->tooltip(function ($record) {
                        return $record->kegiatan;
                    })
                    ->wrap(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'submitted',
                    ])
                    ->formatStateUsing(fn () => 'Perlu Approval'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->since()
                    ->sortable(),
            ])
            ->emptyStateHeading('Tidak ada laporan yang menunggu approval')
            ->emptyStateDescription('Semua laporan sudah ditinjau!')
            ->emptyStateIcon('heroicon-o-check-badge')
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10, 25])
            ->actions([
                $this->reviewReportAction(),
            ])
            ->recordAction('reviewReport');
    }

    public function reviewReportAction(): Action
    {
        return Action::make('reviewReport')
            ->label('Review')
            ->color('primary')
            ->icon('heroicon-o-pencil-square')
            ->fillForm(function (ActivityReport $record) {
                return [
                    'petugas_id' => $record->petugas_id,
                    'lokasi_id' => $record->lokasi_id,
                    'tanggal' => $record->tanggal,
                    'jam_mulai' => $record->jam_mulai,
                    'jam_selesai' => $record->jam_selesai,
                    'kegiatan' => $record->kegiatan,
                    'foto_sebelum' => $record->foto_sebelum,
                    'foto_sesudah' => $record->foto_sesudah,
                    'catatan_petugas' => $record->catatan_petugas,
                    'status' => $record->status,
                    'rating' => $record->rating,
                    'catatan_supervisor' => $record->catatan_supervisor,
                    'rejected_reason' => $record->rejected_reason,
                    'approved_by' => $record->approved_by,
                ];
            })
            ->form([
                // Custom Header View
                ViewField::make('header_content')
                    ->view('filament.widgets.review-laporan-content')
                    ->viewData(fn (ActivityReport $record) => ['record' => $record])
                    ->columnSpanFull(),

                // Review Section - Only editable fields
                Select::make('status')
                    ->label('Status Approval')
                    ->options([
                        'submitted' => 'Submitted',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->required()
                    ->native(false)
                    ->reactive()
                    ->columnSpanFull(),

                TextInput::make('rating')
                    ->label('Rating (1-5)')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(5)
                    ->step(1)
                    ->required()
                    ->helperText('Berikan rating untuk kualitas pekerjaan')
                    ->visible(fn ($get) => $get('status') === 'approved')
                    ->columnSpanFull(),

                Textarea::make('catatan_supervisor')
                    ->label('Catatan Supervisor')
                    ->rows(3)
                    ->columnSpanFull()
                    ->placeholder('Tambahkan catatan untuk petugas...')
                    ->required(fn ($get) => $get('status') !== 'submitted')
                    ->helperText('Catatan wajib diisi saat approve atau reject'),

                Textarea::make('rejected_reason')
                    ->label('Alasan Penolakan')
                    ->rows(3)
                    ->columnSpanFull()
                    ->required()
                    ->placeholder('Jelaskan alasan penolakan...')
                    ->visible(fn ($get) => $get('status') === 'rejected'),

                TextInput::make('approved_by')
                    ->label('Disetujui Oleh')
                    ->default(Auth::user()->name)
                    ->disabled()
                    ->dehydrated()
                    ->formatStateUsing(fn () => Auth::user()->name)
                    ->helperText('Otomatis terisi sesuai akun yang login')
                    ->visible(fn ($get) => in_array($get('status'), ['approved', 'rejected']))
                    ->columnSpanFull(),
            ])
            ->action(function (ActivityReport $record, array $data) {
                // Set approved_by to current user ID
                $data['approved_by'] = Auth::id();

                // Auto-set approved_at timestamp when approved
                if ($data['status'] === 'approved' && !$record->approved_at) {
                    $data['approved_at'] = now();
                }

                $record->update($data);

                // Send success notification
                \Filament\Notifications\Notification::make()
                    ->title('Laporan berhasil diperbarui')
                    ->success()
                    ->send();
            })
            ->modalHeading(fn (ActivityReport $record) => '📋 Review Laporan - ' . $record->petugas->name)
            ->modalSubmitActionLabel('💾 Simpan Review')
            ->modalCancelActionLabel('Batal')
            ->modalWidth('6xl');
    }
}
