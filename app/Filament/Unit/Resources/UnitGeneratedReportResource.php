<?php

namespace App\Filament\Unit\Resources;

use App\Filament\Unit\Resources\UnitGeneratedReportResource\Pages;
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

class UnitGeneratedReportResource extends Resource
{
    protected static ?string $model = UnitReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Laporan Unit';

    protected static ?string $navigationGroup = 'Management';

    protected static ?string $modelLabel = 'Laporan';

    protected static ?string $pluralModelLabel = 'Laporan Unit';

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
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
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Laporan Harian Tekanan Air'),

                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->placeholder('Deskripsi laporan (opsional)')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('report_format')
                            ->label('Format Laporan')
                            ->required()
                            ->options([
                                'summary' => '📊 Ringkasan',
                                'detailed' => '📋 Detail',
                                'statistical' => '📈 Statistik',
                            ]),

                        Forms\Components\Select::make('file_type')
                            ->label('Tipe File')
                            ->required()
                            ->options([
                                'pdf' => 'PDF',
                                'csv' => 'CSV',
                            ])
                            ->default('pdf'),
                    ])->columns(2),

                Forms\Components\Section::make('Sumber Data')
                    ->schema([
                        Forms\Components\Select::make('data_source')
                            ->label('Sumber Data')
                            ->required()
                            ->options([
                                'all' => '🏢 Semua Perangkat',
                                'device' => '📱 Perangkat Spesifik',
                            ])
                            ->reactive(),

                        Forms\Components\Select::make('device_id')
                            ->label('Pilih Perangkat')
                            ->options($devices)
                            ->visible(fn (callable $get) => $get('data_source') === 'device')
                            ->required(fn (callable $get) => $get('data_source') === 'device'),
                    ])->columns(2),

                Forms\Components\Section::make('Periode Laporan')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->required()
                            ->default(now()->subDays(7)),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('Tanggal Akhir')
                            ->required()
                            ->default(now())
                            ->after('start_date'),
                    ])->columns(2),
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

                Tables\Columns\BadgeColumn::make('report_format')
                    ->label('Format')
                    ->colors([
                        'info' => 'summary',
                        'success' => 'detailed',
                        'warning' => 'statistical',
                    ]),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Periode')
                    ->formatStateUsing(fn ($record) => 
                        $record->start_date->format('d/m/Y') . ' - ' . $record->end_date->format('d/m/Y')
                    ),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'secondary' => 'pending',
                        'warning' => 'processing',
                        'success' => 'completed',
                        'danger' => 'failed',
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('generate')
                    ->label('Generate')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->color('primary')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->action(function ($record) {
                        $record->update(['status' => 'processing']);
                        
                        Notification::make()
                            ->title('Laporan Diproses')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'completed'),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUnitGeneratedReports::route('/'),
            'create' => Pages\CreateUnitGeneratedReport::route('/create'),
            'view' => Pages\ViewUnitGeneratedReport::route('/{record}'),
            'edit' => Pages\EditUnitGeneratedReport::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        if (!Auth::check()) return null;
        
        $user = Auth::user();
        if (!$user->unit_id) return null;
        
        $count = static::getModel()::where('unit_id', $user->unit_id)->count();
        return $count > 0 ? (string) $count : null;
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