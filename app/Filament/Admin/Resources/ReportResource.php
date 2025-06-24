<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ReportResource\Pages;
use App\Models\Report;
use App\Models\Device;
use App\Models\DeviceGroup;
use App\Services\ReportService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;


class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Laporan';

    protected static ?string $modelLabel = 'Laporan';

    protected static ?string $pluralModelLabel = 'Laporan';

    protected static ?string $navigationGroup = 'Analitik';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Laporan')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Laporan')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('type')
                            ->label('Tipe Laporan')
                            ->options([
                                'daily' => 'Harian',
                                'weekly' => 'Mingguan',
                                'monthly' => 'Bulanan',
                                'custom' => 'Kustom',
                            ])
                            ->default('custom')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state === 'daily') {
                                    $set('start_date', Carbon::today());
                                    $set('end_date', Carbon::today());
                                } elseif ($state === 'weekly') {
                                    $set('start_date', Carbon::now()->startOfWeek());
                                    $set('end_date', Carbon::now()->endOfWeek());
                                } elseif ($state === 'monthly') {
                                    $set('start_date', Carbon::now()->startOfMonth());
                                    $set('end_date', Carbon::now()->endOfMonth());
                                }
                            })
                            ->required(),

                        Forms\Components\DatePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->required()
                            ->maxDate(fn(callable $get) => $get('end_date')),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('Tanggal Akhir')
                            ->required()
                            ->minDate(fn(callable $get) => $get('start_date')),

                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Sumber Data')
                    ->schema([
                        Forms\Components\Select::make('data_source')
                            ->label('Sumber Data')
                            ->options([
                                'all' => 'Semua Perangkat',
                                'device' => 'Perangkat Spesifik',
                                'group' => 'Grup Perangkat',
                            ])
                            ->default('all')
                            ->reactive()
                            ->afterStateUpdated(function (string $state, callable $set) {
                                if ($state === 'all') {
                                    $set('device_id', null);
                                    $set('device_group_id', null);
                                }
                            })
                            ->required(),

                        Forms\Components\Select::make('device_id')
                            ->label('Perangkat')
                            ->options(fn() => Device::all()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->visible(fn(callable $get) => $get('data_source') === 'device'),

                        Forms\Components\Select::make('device_group_id')
                            ->label('Grup Perangkat')
                            ->options(fn() => DeviceGroup::all()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->visible(fn(callable $get) => $get('data_source') === 'group'),
                    ]),

                Forms\Components\Section::make('Penjadwalan & Pengiriman')
                    ->schema([
                        Forms\Components\Toggle::make('is_scheduled')
                            ->label('Jadwalkan Pembuatan Laporan')
                            ->reactive()
                            ->default(false),

                        Forms\Components\Select::make('schedule_frequency')
                            ->label('Frekuensi')
                            ->options([
                                'daily' => 'Harian',
                                'weekly' => 'Mingguan',
                                'monthly' => 'Bulanan',
                                'quarterly' => '3 Bulanan',
                            ])
                            ->visible(fn(callable $get) => $get('is_scheduled'))
                            ->required(fn(callable $get) => $get('is_scheduled')),

                        Forms\Components\Toggle::make('email_on_completion')
                            ->label('Kirim Laporan via Email')
                            ->reactive()
                            ->default(false),

                        Forms\Components\TextInput::make('email_recipients')
                            ->label('Penerima Email')
                            ->placeholder('email1@example.com, email2@example.com')
                            ->helperText('Pisahkan dengan koma untuk banyak penerima')
                            ->visible(fn(callable $get) => $get('email_on_completion'))
                            ->required(fn(callable $get) => $get('email_on_completion')),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Parameter Tambahan')
                    ->schema([
                        Forms\Components\Toggle::make('include_anomalies')
                            ->label('Sertakan Deteksi Anomali')
                            ->helperText('Deteksi data yang menyimpang dari nilai normal')
                            ->default(true),

                        Forms\Components\Toggle::make('include_charts')
                            ->label('Sertakan Grafik')
                            ->helperText('Tampilkan grafik tren data dalam laporan')
                            ->default(true),

                        Forms\Components\Select::make('anomaly_threshold')
                            ->label('Ambang Anomali')
                            ->options([
                                '1.5' => 'Rendah (1.5 Std Dev)',
                                '2.0' => 'Sedang (2 Std Dev)',
                                '3.0' => 'Tinggi (3 Std Dev)',
                            ])
                            ->default('2.0')
                            ->visible(fn(callable $get) => $get('include_anomalies')),

                        Forms\Components\Select::make('chart_type')
                            ->label('Tipe Grafik')
                            ->options([
                                'line' => 'Garis',
                                'bar' => 'Batang',
                                'area' => 'Area',
                            ])
                            ->default('line')
                            ->visible(fn(callable $get) => $get('include_charts')),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Laporan')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'daily' => 'Harian',
                        'weekly' => 'Mingguan',
                        'monthly' => 'Bulanan',
                        default => 'Kustom',
                    })
                    ->colors([
                        'primary' => 'daily',
                        'success' => 'weekly',
                        'warning' => 'monthly',
                        'gray' => 'custom',
                    ]),

                Tables\Columns\TextColumn::make('dataSourceName')
                    ->label('Sumber Data')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Dari')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Sampai')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_scheduled')
                    ->label('Terjadwal')
                    ->boolean(),

                Tables\Columns\TextColumn::make('schedule_frequency')
                    ->label('Frekuensi')
                    ->formatStateUsing(fn(?string $state): string => match ($state) {
                        'daily' => 'Harian',
                        'weekly' => 'Mingguan',
                        'monthly' => 'Bulanan',
                        'quarterly' => '3 Bulanan',
                        default => '-',
                    })
                    ->visible(fn(): bool => !request()->has('tableSearch')),

                Tables\Columns\IconColumn::make('email_on_completion')
                    ->label('Email')
                    ->boolean()
                    ->visible(fn(): bool => !request()->has('tableSearch')),

                Tables\Columns\TextColumn::make('last_generated_at')
                    ->label('Terakhir Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->placeholder('-'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe Laporan')
                    ->options([
                        'daily' => 'Harian',
                        'weekly' => 'Mingguan',
                        'monthly' => 'Bulanan',
                        'custom' => 'Kustom',
                    ]),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('start_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('end_date', '<=', $date),
                            );
                    }),

                Tables\Filters\SelectFilter::make('is_scheduled')
                    ->label('Status Penjadwalan')
                    ->options([
                        '1' => 'Terjadwal',
                        '0' => 'Manual',
                    ]),

                Tables\Filters\SelectFilter::make('device_id')
                    ->label('Perangkat')
                    ->relationship('device', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('device_group_id')
                    ->label('Grup Perangkat')
                    ->relationship('deviceGroup', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),


                Tables\Actions\Action::make('generate')
                    ->label('Generate')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function (Report $record) {
                        try {
                            // Tambahkan notifikasi bahwa proses dimulai
                            Notification::make()
                                ->title('Memproses laporan...')
                                ->body('Mohon tunggu sebentar, laporan sedang dibuat.')
                                ->info()
                                ->send();

                            $reportService = app(ReportService::class);
                            $data = $reportService->generateReport($record);
                            $filePath = $reportService->generatePDF($record, $data);

                            // Debugging
                            Log::info("Generated report file: " . $filePath);
                            Log::info("File exists: " . (Storage::exists($filePath) ? 'Yes' : 'No'));

                            // Jika file berhasil dibuat
                            if ($filePath && Storage::exists($filePath)) {
                                // Tampilkan notifikasi sukses
                                Notification::make()
                                    ->title('Laporan berhasil dibuat')
                                    ->body('Laporan telah berhasil dibuat dan siap untuk didownload.')
                                    ->success()
                                    ->actions([
                                        // PERBAIKAN: Gunakan NotificationAction bukan Action
                                        NotificationAction::make('download')
                                            ->label('Download')
                                            ->url(route('reports.download', $record))
                                            ->openUrlInNewTab(),
                                    ])
                                    ->persistent()
                                    ->send();

                                return redirect()->back();
                            } else {
                                // Jika file tidak ada, tampilkan error
                                Notification::make()
                                    ->title('Gagal membuat laporan')
                                    ->body('File laporan tidak dapat dibuat. Silakan coba lagi.')
                                    ->danger()
                                    ->send();

                                return redirect()->back();
                            }
                        } catch (\Exception $e) {
                            // Tangkap error jika ada
                            Log::error("Error generating report: " . $e->getMessage());

                            Notification::make()
                                ->title('Error')
                                ->body('Terjadi kesalahan: ' . $e->getMessage())
                                ->danger()
                                ->send();

                            return redirect()->back();
                        }
                    }),

                Tables\Actions\Action::make('exportCsv')
                    ->label('Export CSV')
                    ->icon('heroicon-o-table-cells')
                    ->color('secondary')
                    ->action(function (Report $record) {
                        $reportService = app(ReportService::class);
                        $data = $reportService->generateReport($record);
                        $filePath = $reportService->generateCSV($record, $data);

                        if ($filePath) {
                            return response()->download(storage_path('app/' . $filePath));
                        }

                        return redirect()->back()->with('error', 'Gagal membuat file CSV');
                    })
                    ->visible(fn(Report $record) => true),
                Tables\Actions\DeleteAction::make()
                    ->modalDescription('Apakah Anda yakin ingin menghapus laporan ini? Semua file laporan yang terkait juga akan dihapus.'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->modalDescription('Apakah Anda yakin ingin menghapus semua laporan yang dipilih? Tindakan ini tidak dapat dibatalkan.'),

                    Tables\Actions\BulkAction::make('generateBulk')
                        ->label('Generate Semua')
                        ->icon('heroicon-o-document-arrow-down')
                        ->action(function (Collection $records) {
                            $reportService = app(ReportService::class);
                            $count = 0;

                            foreach ($records as $record) {
                                try {
                                    $data = $reportService->generateReport($record);
                                    $reportService->generatePDF($record, $data);
                                    $count++;
                                } catch (\Exception $e) {
                                    // Lanjutkan ke record berikutnya jika terjadi error
                                    continue;
                                }
                            }

                            return redirect()->back()->with('success', "{$count} laporan berhasil dibuat");
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Generate Banyak Laporan')
                        ->modalDescription('Apakah Anda yakin ingin membuat semua laporan yang dipilih? Proses ini mungkin membutuhkan waktu cukup lama.')
                        ->modalSubmitActionLabel('Ya, Generate Semua')
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReports::route('/'),
            'create' => Pages\CreateReport::route('/create'),
            'edit' => Pages\EditReport::route('/{record}/edit'),
            'view' => Pages\ViewReport::route('/{record}/view'),
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['device', 'deviceGroup']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'description', 'device.name', 'deviceGroup.name'];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('is_scheduled', true)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Analitik');
    }

    public static function canDelete(Model $record): bool
    {
        // Hapus file PDF yang terkait saat menghapus record
        if ($record->last_generated_file && Storage::exists($record->last_generated_file)) {
            Storage::delete($record->last_generated_file);
        }

        return true;
    }
}
