<?php

namespace App\Filament\Widgets;

use App\Models\ActivityReport;
use App\Models\LaporanSatpam;
use App\Models\LaporanOb;
use App\Models\LaporanToko;
use App\Models\Unit;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        $kebersihan = DB::table('activity_reports')
            ->select('id', 'tanggal', 'petugas_id', 'lokasi_id', 'status', 'created_at', 'kegiatan as deskripsi', DB::raw("'kebersihan' as service_type"))
            ->where('status', 'submitted')
            ->whereNull('deleted_at');

        $satpam = DB::table('laporan_satpam')
            ->select('id', 'tanggal', 'petugas_id', 'lokasi_id', 'status', 'created_at', 'temuan as deskripsi', DB::raw("'satpam' as service_type"))
            ->where('status', 'submitted')
            ->whereNull('deleted_at');

        $ob = DB::table('laporan_ob')
            ->select('id', 'tanggal', 'petugas_id', 'lokasi_id', 'status', 'created_at', 'uraian as deskripsi', DB::raw("'ob' as service_type"))
            ->where('status', 'submitted')
            ->whereNull('deleted_at');

        $toko = DB::table('laporan_toko')
            ->select('id', 'tanggal', 'petugas_id', 'lokasi_id', 'status', 'created_at', 'catatan_petugas as deskripsi', DB::raw("'toko' as service_type"))
            ->where('status', 'submitted')
            ->whereNull('deleted_at');

        $unionQuery = $kebersihan->unionAll($satpam)->unionAll($ob)->unionAll($toko);

        $model = new ActivityReport();
        $model->setTable('all_reports');

        return $table
            ->query(
                $model->newQuery()
                    ->withTrashed()
                    ->fromSub($unionQuery, 'all_reports')
                    ->with(['petugas', 'lokasi.unit'])
            )
            ->columns([
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('service_type')
                    ->label('Divisi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'kebersihan' => 'success',
                        'satpam' => 'danger',
                        'ob' => 'warning',
                        'toko' => 'info',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'kebersihan' => 'Kebersihan',
                        'satpam' => 'Security (Satpam)',
                        'ob' => 'Office Boy',
                        'toko' => 'Petugas Toko',
                    })
                    ->sortable(),

                TextColumn::make('petugas.name')
                    ->label('Petugas')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('lokasi.unit.nama_unit')
                    ->label('Unit/Kampus')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('lokasi.nama_lokasi')
                    ->label('Lokasi')
                    ->searchable()
                    ->wrap()
                    ->sortable(),

                TextColumn::make('deskripsi')
                    ->label('Kegiatan/Temuan')
                    ->limit(50)
                    ->tooltip(function ($record) {
                        return $record->deskripsi;
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
            ->filters([
                SelectFilter::make('service_type')
                    ->label('Filter Divisi/Layanan')
                    ->options([
                        'kebersihan' => 'Kebersihan (Cleaning)',
                        'satpam' => 'Security (Satpam)',
                        'ob' => 'Office Boy (OB)',
                        'toko' => 'Petugas Toko (Toko)',
                    ])
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            $query->where('service_type', $data['value']);
                        }
                    })
                    ->placeholder('Semua Divisi'),

                SelectFilter::make('unit_id')
                    ->label('Filter Unit/Kampus')
                    ->options(
                        Unit::where('is_active', true)
                            ->orderBy('nama_unit')
                            ->pluck('nama_unit', 'id')
                    )
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereHas('lokasi', function ($q) use ($data) {
                                $q->where('unit_id', $data['value']);
                            });
                        }
                    })
                    ->searchable()
                    ->preload()
                    ->placeholder('Semua Unit'),
            ])
            ->filtersFormColumns(2)
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
                $actualRecord = match ($record->service_type ?? 'kebersihan') {
                    'kebersihan' => ActivityReport::find($record->id),
                    'satpam' => LaporanSatpam::find($record->id),
                    'ob' => LaporanOb::find($record->id),
                    'toko' => LaporanToko::find($record->id),
                };

                if (!$actualRecord) {
                    return [];
                }

                return [
                    'petugas_id' => $actualRecord->petugas_id,
                    'lokasi_id' => $actualRecord->lokasi_id,
                    'tanggal' => $actualRecord->tanggal,
                    'jam_mulai' => $actualRecord->jam_mulai,
                    'jam_selesai' => $actualRecord->jam_selesai,
                    'kegiatan' => match ($record->service_type ?? 'kebersihan') {
                        'kebersihan' => $actualRecord->kegiatan,
                        'satpam' => "Kondisi: {$actualRecord->kondisi}\nTemuan: {$actualRecord->temuan}\nTindakan: {$actualRecord->tindakan}",
                        'ob' => "Jenis Pekerjaan: {$actualRecord->jenis_pekerjaan}\nUraian: {$actualRecord->uraian}",
                        'toko' => "Kondisi Stok: {$actualRecord->kondisi_stok}\nCatatan Stok: {$actualRecord->catatan_stok}",
                    },
                    'foto_sebelum' => match ($record->service_type ?? 'kebersihan') {
                        'kebersihan', 'ob' => $actualRecord->foto_sebelum,
                        'satpam', 'toko' => $actualRecord->foto,
                    },
                    'foto_sesudah' => match ($record->service_type ?? 'kebersihan') {
                        'kebersihan', 'ob' => $actualRecord->foto_sesudah,
                        default => null,
                    },
                    'catatan_petugas' => $actualRecord->catatan_petugas,
                    'status' => $actualRecord->status,
                    'rating' => $actualRecord->rating,
                    'catatan_supervisor' => $actualRecord->catatan_supervisor,
                    'rejected_reason' => $actualRecord->rejected_reason,
                    'approved_by' => $actualRecord->approved_by,
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
                // Find and update the actual model
                $actualRecord = match ($record->service_type ?? 'kebersihan') {
                    'kebersihan' => ActivityReport::find($record->id),
                    'satpam' => LaporanSatpam::find($record->id),
                    'ob' => LaporanOb::find($record->id),
                    'toko' => LaporanToko::find($record->id),
                };

                if ($actualRecord) {
                    $updateData = [
                        'status' => $data['status'],
                        'approved_by' => Auth::id(),
                        'catatan_supervisor' => $data['catatan_supervisor'] ?? null,
                    ];

                    if ($data['status'] === 'approved') {
                        $updateData['approved_at'] = now();
                        $updateData['rating'] = $data['rating'] ?? null;
                        $updateData['rejected_reason'] = null;
                    } elseif ($data['status'] === 'rejected') {
                        $updateData['approved_at'] = now();
                        $updateData['rejected_reason'] = $data['rejected_reason'] ?? null;
                        $updateData['rating'] = null;
                    } else {
                        $updateData['approved_at'] = null;
                        $updateData['rejected_reason'] = null;
                        $updateData['rating'] = null;
                    }

                    $actualRecord->update($updateData);

                    // Send push notification if petugas exists
                    if ($actualRecord->petugas) {
                        $title = $data['status'] === 'approved' ? 'Laporan Disetujui' : 'Laporan Ditolak';
                        $body = $data['status'] === 'approved' 
                            ? 'Laporan kegiatan Anda telah disetujui supervisor.' 
                            : 'Laporan kegiatan Anda ditolak: ' . \Illuminate\Support\Str::limit($data['rejected_reason'] ?? '', 80);
                        
                        try {
                            app(\App\Services\ExpoPushService::class)->sendToUser(
                                $actualRecord->petugas,
                                $title,
                                $body,
                                ['type' => "report_{$data['status']}", 'report_id' => $actualRecord->id]
                            );
                        } catch (\Exception $e) {
                            // ignore push service exceptions gracefully
                        }
                    }
                }

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
