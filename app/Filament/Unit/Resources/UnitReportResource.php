<?php

namespace App\Filament\Unit\Resources;

use App\Filament\Unit\Resources\UnitReportResource\Pages;
use App\Models\UnitReport;
use App\Models\Device;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class UnitReportResource extends Resource
{
    protected static ?string $model = UnitReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Laporan Unit';

    protected static ?string $navigationGroup = 'Management';

    protected static ?string $modelLabel = 'Laporan';

    protected static ?string $pluralModelLabel = 'Laporan Unit';

    protected static bool $shouldRegisterNavigation = false;

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        if (!$user || !$user->unit_id) {
            // Return empty query jika user tidak punya unit_id
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        return parent::getEloquentQuery()->where('unit_id', $user->unit_id);
    }

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $devices = Device::where('unit_id', $user->unit_id)->pluck('name', 'id');

        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Laporan')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Laporan')
                            ->required()  // ✅ REQUIRED sesuai migration
                            ->maxLength(255)
                            ->placeholder('Contoh: Laporan Harian Tekanan Air')
                            ->default('Laporan Monitoring ' . now()->format('d/m/Y')),  // ✅ PROVIDE DEFAULT

                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->placeholder('Deskripsi laporan (opsional)'),

                        Forms\Components\Select::make('report_format')
                            ->label('Format Laporan')
                            ->required()
                            ->options([
                                'summary' => '📊 Ringkasan',
                                'detailed' => '📋 Detail',
                                'statistical' => '📈 Statistik',
                            ])
                            ->default('summary'),  // ✅ SESUAI MIGRATION DEFAULT

                        Forms\Components\Select::make('file_type')
                            ->label('Tipe File')
                            ->required()
                            ->options([
                                'pdf' => 'PDF',
                                'csv' => 'CSV',
                                'excel' => 'Excel',
                            ])
                            ->default('pdf'),  // ✅ SESUAI MIGRATION DEFAULT
                    ])->columns(2),

                Forms\Components\Section::make('Sumber Data')
                    ->schema([
                        Forms\Components\Select::make('data_source')
                            ->label('Sumber Data')
                            ->required()
                            ->options([
                                'all' => '🏢 Semua Perangkat',
                                'device' => '📱 Perangkat Spesifik',
                                'group' => '📂 Grup Perangkat',  // ✅ SESUAI MIGRATION 'group'
                            ])
                            ->default('all')  // ✅ SESUAI MIGRATION DEFAULT
                            ->live(),

                        Forms\Components\Select::make('device_id')
                            ->label('Pilih Perangkat')
                            ->options($devices)
                            ->searchable()
                            ->visible(fn(Forms\Get $get) => $get('data_source') === 'device')
                            ->required(fn(Forms\Get $get) => $get('data_source') === 'device'),

                        Forms\Components\Select::make('device_group_id')
                            ->label('Pilih Grup Perangkat')
                            ->options([])  // ✅ EMPTY OPTIONS FOR NOW
                            ->searchable()
                            ->visible(fn(Forms\Get $get) => $get('data_source') === 'group')
                            ->required(fn(Forms\Get $get) => $get('data_source') === 'group'),
                    ])->columns(2),

                Forms\Components\Section::make('Periode Laporan')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->required()  // ✅ REQUIRED sesuai migration
                            ->default(now()->subDays(7)),  // ✅ PROVIDE DEFAULT

                        Forms\Components\DatePicker::make('end_date')
                            ->label('Tanggal Akhir')
                            ->required()  // ✅ REQUIRED sesuai migration
                            ->default(now())  // ✅ PROVIDE DEFAULT
                            ->after('start_date'),
                    ])->columns(2),

                // ✅ HIDDEN FIELDS UNTUK ENSURE SEMUA REQUIRED FIELDS ADA
                Forms\Components\Hidden::make('status')
                    ->default('pending'),

                Forms\Components\Hidden::make('metrics')
                    ->default(null),

                Forms\Components\Hidden::make('file_path')
                    ->default(null),

                Forms\Components\Hidden::make('generated_at')
                    ->default(null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Laporan')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                Tables\Columns\BadgeColumn::make('report_format')
                    ->label('Format')
                    ->colors([
                        'info' => 'summary',
                        'success' => 'detailed',
                        'warning' => 'statistical',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'summary' => 'Ringkasan',
                        'detailed' => 'Detail',
                        'statistical' => 'Statistik',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Periode')
                    ->formatStateUsing(
                        fn($record) =>
                        $record->start_date->format('d/m/Y') . ' - ' . $record->end_date->format('d/m/Y')
                    ),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'secondary' => 'pending',
                        'warning' => 'processing',
                        'success' => 'completed',
                        'danger' => 'failed',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'pending' => 'Pending',
                        'processing' => 'Proses',
                        'completed' => 'Selesai',
                        'failed' => 'Gagal',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Proses',
                        'completed' => 'Selesai',
                        'failed' => 'Gagal',
                    ]),

                Tables\Filters\SelectFilter::make('report_format')
                    ->label('Format')
                    ->options([
                        'summary' => 'Ringkasan',
                        'detailed' => 'Detail',
                        'statistical' => 'Statistik',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListUnitReports::route('/'),
            'create' => Pages\CreateUnitReport::route('/create'),
            'view' => Pages\ViewUnitReport::route('/{record}'),  // ✅ BUKAN ViewReport
            'edit' => Pages\EditUnitReport::route('/{record}/edit'),
        ];
    }

    // ✅ UBAH DARI protected MENJADI public
    public static function getNavigationBadge(): ?string
    {
        if (!Auth::check()) {
            return null;
        }

        $user = Auth::user();

        if (!$user->unit_id) {
            return null;
        }

        $count = static::getModel()::where('unit_id', $user->unit_id)->count();

        return $count > 0 ? (string) $count : null;
    }

    // ✅ TAMBAH METHOD getNavigationBadgeColor() juga public
    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function canDelete($record): bool
    {
        return $record->status === 'pending' || $record->status === 'failed';
    }

    public static function canEdit($record): bool
    {
        return $record->status === 'pending' || $record->status === 'failed';
    }
}
