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
                            ->required(),

                        Forms\Components\Select::make('report_format')
                            ->label('Format Laporan')
                            ->options([
                                'summary' => 'Ringkasan',
                                'detailed' => 'Detail',
                                'statistical' => 'Statistik (Min/Max/Volume)',
                            ])
                            ->default('summary')
                            ->reactive()
                            ->required(),

                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Metrics & Data')
                    ->schema([
                        Forms\Components\CheckboxList::make('metrics')
                            ->label('Metrics yang Disertakan')
                            ->options([
                                'flowrate' => 'Flowrate (l/s)',
                                'pressure1' => 'Tekanan 1 (bar)',
                                'pressure2' => 'Tekanan 2 (bar)',
                                'totalizer' => 'Totalizer (m³)',
                                'battery' => 'Battery (Volt)',
                            ])
                            ->default(['flowrate', 'totalizer'])
                            ->visible(fn(callable $get) => $get('report_format') === 'statistical')
                            ->required(fn(callable $get) => $get('report_format') === 'statistical')
                            ->columns(2),

                        Forms\Components\Select::make('data_source')
                            ->label('Sumber Data')
                            ->options([
                                'all' => 'Semua Perangkat',
                                'device' => 'Perangkat Spesifik',
                                'group' => 'Grup Perangkat',
                            ])
                            ->default('all')
                            ->reactive()
                            ->required(),

                        Forms\Components\Select::make('device_id')
                            ->label('Pilih Perangkat')
                            ->relationship('device', 'name')
                            ->visible(fn(callable $get) => $get('data_source') === 'device')
                            ->required(fn(callable $get) => $get('data_source') === 'device'),

                        Forms\Components\Select::make('device_group_id')
                            ->label('Pilih Grup Perangkat')
                            ->relationship('deviceGroup', 'name')
                            ->visible(fn(callable $get) => $get('data_source') === 'group')
                            ->required(fn(callable $get) => $get('data_source') === 'group'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Periode Laporan')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->required()
                            ->default(now()->startOfMonth()),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('Tanggal Selesai')
                            ->required()
                            ->default(now()->endOfMonth())
                            ->after('start_date'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Penjadwalan & Pengiriman')
                    ->schema([
                        Forms\Components\Toggle::make('is_scheduled')
                            ->label('Jadwalkan Laporan')
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
                            ->reactive()
                            ->default(false),

                        Forms\Components\TextInput::make('anomaly_threshold')
                            ->label('Threshold Anomali')
                            ->numeric()
                            ->step(0.01)
                            ->visible(fn(callable $get) => $get('include_anomalies'))
                            ->required(fn(callable $get) => $get('include_anomalies')),

                        Forms\Components\Toggle::make('include_charts')
                            ->label('Sertakan Grafik')
                            ->reactive()
                            ->default(false),

                        Forms\Components\Select::make('chart_type')
                            ->label('Tipe Grafik')
                            ->options([
                                'line' => 'Garis',
                                'bar' => 'Batang',
                                'area' => 'Area',
                            ])
                            ->visible(fn(callable $get) => $get('include_charts'))
                            ->required(fn(callable $get) => $get('include_charts')),
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
                        'custom' => 'Kustom',
                        default => 'Tidak Diketahui',
                    }),

                Tables\Columns\TextColumn::make('report_format')
                    ->label('Format')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'summary' => 'Ringkasan',
                        'detailed' => 'Detail',
                        'statistical' => 'Statistik',
                        default => 'Ringkasan',
                    })
                    ->colors([
                        'primary' => 'summary',
                        'success' => 'detailed',
                        'warning' => 'statistical',
                    ]),

                Tables\Columns\TextColumn::make('data_source_name')
                    ->label('Sumber Data')
                    ->getStateUsing(fn(Report $record): string => $record->dataSourceName),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Mulai')
                    ->date('d/m/Y'),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Selesai')
                    ->date('d/m/Y'),

                Tables\Columns\IconColumn::make('is_scheduled')
                    ->label('Terjadwal')
                    ->boolean(),

                Tables\Columns\TextColumn::make('last_generated_at')
                    ->label('Terakhir Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe')
                    ->options([
                        'daily' => 'Harian',
                        'weekly' => 'Mingguan',
                        'monthly' => 'Bulanan',
                        'custom' => 'Kustom',
                    ]),

                Tables\Filters\SelectFilter::make('report_format')
                    ->label('Format')
                    ->options([
                        'summary' => 'Ringkasan',
                        'detailed' => 'Detail',
                        'statistical' => 'Statistik',
                    ]),

                Tables\Filters\Filter::make('scheduled')
                    ->label('Hanya Terjadwal')
                    ->query(fn(Builder $query): Builder => $query->where('is_scheduled', true)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('generate')
                    ->label('Generate')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function (Report $record) {
                        // Redirect ke generate route
                        return redirect()->route('reports.generate', $record->id);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Generate Laporan')
                    ->modalDescription('Apakah Anda yakin ingin membuat laporan ini?')
                    ->modalSubmitActionLabel('Ya, Generate'),

                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->url(fn(Report $record): string => route('reports.download', $record->id))
                    ->openUrlInNewTab()
                    ->visible(fn(Report $record): bool => $record->last_generated_file !== null),

                Tables\Actions\Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn(Report $record): string => route('reports.preview', $record->id))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
