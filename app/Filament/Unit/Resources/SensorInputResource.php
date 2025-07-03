<?php
// filepath: app/Filament/Unit/Resources/SensorInputResource.php

namespace App\Filament\Unit\Resources;

use App\Filament\Unit\Resources\SensorInputResource\Pages;
use App\Models\Device;
use App\Models\SensorData;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class SensorInputResource extends Resource
{
    protected static ?string $model = SensorData::class;

    protected static ?string $navigationIcon = 'heroicon-o-plus-circle';

    protected static ?string $navigationLabel = 'Input Data Sensor';

    protected static ?string $modelLabel = 'Data Sensor';

    protected static ?string $pluralModelLabel = 'Input Data Sensor';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Input Data Sensor Manual')
                    ->description('Masukkan data sensor secara manual untuk perangkat unit Anda')
                    ->icon('heroicon-o-plus-circle')
                    ->schema([
                        // DEVICE SELECTOR - HANYA PERANGKAT UNIT INI
                        Forms\Components\Select::make('device_id')
                            ->label('Pilih Perangkat')
                            ->placeholder('Pilih perangkat untuk input data')
                            ->options(function () {
                                $user = Auth::user();
                                $unit = $user?->unit;
                                
                                if (!$unit) {
                                    return [];
                                }
                                
                                // HANYA AMBIL PERANGKAT YANG TERKAIT DENGAN UNIT INI
                                return $unit->devices()
                                    ->select('id', 'name', 'location')
                                    ->get()
                                    ->mapWithKeys(function ($device) {
                                        $label = $device->name;
                                        if ($device->location) {
                                            $label .= ' (' . $device->location . ')';
                                        }
                                        return [$device->id => $label];
                                    })
                                    ->toArray();
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live(debounce: 500)
                            ->afterStateUpdated(function (callable $set, $state) {
                                if ($state) {
                                    // VALIDASI DEVICE MILIK UNIT INI
                                    $user = Auth::user();
                                    $unit = $user?->unit;
                                    
                                    if ($unit) {
                                        $device = Device::where('id', $state)
                                                       ->where('unit_id', $unit->id)
                                                       ->first();
                                        
                                        if (!$device) {
                                            Notification::make()
                                                ->title('Error')
                                                ->body('Perangkat tidak ditemukan atau tidak memiliki akses')
                                                ->danger()
                                                ->send();
                                            $set('device_id', null);
                                            return;
                                        }
                                        
                                        // Auto-fill dengan data terakhir
                                        $lastData = SensorData::where('device_id', $state)
                                            ->orderBy('recorded_at', 'desc')
                                            ->first();
                                        
                                        if ($lastData) {
                                            $set('totalizer', $lastData->totalizer);
                                            $set('battery', $lastData->battery);
                                            
                                            Notification::make()
                                                ->title('Data Referensi')
                                                ->body("Totalizer terakhir: {$lastData->totalizer} L, Battery: {$lastData->battery}%")
                                                ->info()
                                                ->duration(5000)
                                                ->send();
                                        }
                                    }
                                }
                            })
                            ->helperText(function () {
                                $user = Auth::user();
                                $unit = $user?->unit;
                                $deviceCount = $unit?->devices()->count() ?? 0;
                                return "Unit Anda memiliki {$deviceCount} perangkat";
                            }),

                        // FORM FIELDS UNTUK INPUT DATA
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('pressure1')
                                    ->label('Tekanan 1')
                                    ->numeric()
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->required()
                                    ->suffix('bar')
                                    ->placeholder('0.00'),

                                Forms\Components\TextInput::make('pressure2')
                                    ->label('Tekanan 2')
                                    ->numeric()
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('bar')
                                    ->placeholder('0.00'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('flowrate')
                                    ->label('Flowrate')
                                    ->numeric()
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->maxValue(1000)
                                    ->required()
                                    ->suffix('L/s')
                                    ->placeholder('0.00'),

                                Forms\Components\TextInput::make('totalizer')
                                    ->label('Totalizer')
                                    ->numeric()
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->required()
                                    ->suffix('L')
                                    ->placeholder('0.00')
                                    ->helperText('Tidak boleh lebih kecil dari data sebelumnya'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('battery')
                                    ->label('Level Baterai')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->required()
                                    ->suffix('%')
                                    ->placeholder('100'),

                                Forms\Components\TextInput::make('error_code')
                                    ->label('Kode Error')
                                    ->maxLength(10)
                                    ->placeholder('E01, E02, dll')
                                    ->helperText('Kosongkan jika tidak ada error'),
                            ]),

                        Forms\Components\DateTimePicker::make('recorded_at')
                            ->label('Waktu Pencatatan')
                            ->default(now())
                            ->required()
                            ->seconds(false)
                            ->displayFormat('d/m/Y H:i')
                            ->native(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                // QUERY HANYA DATA SENSOR DARI PERANGKAT UNIT INI
                SensorData::query()
                    ->whereHas('device', function (Builder $query) {
                        $user = Auth::user();
                        $unit = $user?->unit;
                        if ($unit) {
                            $query->where('unit_id', $unit->id);
                        }
                    })
                    ->with(['device:id,name,location,unit_id'])
                    ->orderBy('recorded_at', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('device.name')
                    ->label('Perangkat')
                    ->sortable()
                    ->searchable()
                    ->description(fn (SensorData $record): string => $record->device->location ?? ''),

                Tables\Columns\TextColumn::make('pressure1')
                    ->label('Tekanan 1')
                    ->suffix(' bar')
                    ->sortable()
                    ->alignEnd()
                    ->formatStateUsing(fn ($state) => number_format($state, 2)),

                Tables\Columns\TextColumn::make('flowrate')
                    ->label('Flowrate')
                    ->suffix(' L/s')
                    ->sortable()
                    ->alignEnd()
                    ->formatStateUsing(fn ($state) => number_format($state, 2)),

                Tables\Columns\TextColumn::make('totalizer')
                    ->label('Totalizer')
                    ->suffix(' L')
                    ->sortable()
                    ->alignEnd()
                    ->formatStateUsing(fn ($state) => number_format($state, 2)),

                Tables\Columns\TextColumn::make('battery')
                    ->label('Baterai')
                    ->suffix('%')
                    ->sortable()
                    ->alignEnd()
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 70 => 'success',
                        $state >= 40 => 'info',
                        $state >= 20 => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('error_code')
                    ->label('Error')
                    ->placeholder('OK')
                    ->badge()
                    ->color(fn ($state) => $state ? 'danger' : 'success')
                    ->formatStateUsing(fn ($state) => $state ?: 'OK'),

                Tables\Columns\TextColumn::make('recorded_at')
                    ->label('Waktu')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->description(fn (SensorData $record): string => $record->recorded_at->diffForHumans()),
            ])
            ->filters([
                // FILTER HANYA PERANGKAT UNIT INI
                Tables\Filters\SelectFilter::make('device_id')
                    ->label('Filter Perangkat')
                    ->placeholder('Semua Perangkat')
                    ->options(function () {
                        $user = Auth::user();
                        $unit = $user?->unit;
                        if (!$unit) return [];
                        
                        return $unit->devices()
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('today')
                    ->label('Hari Ini')
                    ->query(fn (Builder $query): Builder => $query->whereDate('recorded_at', today()))
                    ->toggle(),

                Tables\Filters\Filter::make('this_week')
                    ->label('Minggu Ini')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('recorded_at', [
                        now()->startOfWeek(),
                        now()->endOfWeek()
                    ]))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('recorded_at', 'desc')
            ->emptyStateHeading('Belum Ada Data Sensor')
            ->emptyStateDescription('Mulai dengan menginput data sensor pertama untuk perangkat unit Anda.')
            ->emptyStateIcon('heroicon-o-chart-bar');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSensorInputs::route('/'),
            'create' => Pages\CreateSensorInput::route('/create'),
            'view' => Pages\ViewSensorInput::route('/{record}'),
            'edit' => Pages\EditSensorInput::route('/{record}/edit'),
        ];
    }

    // QUERY SCOPE - HANYA DATA UNIT INI
    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $unit = $user?->unit;
        
        return parent::getEloquentQuery()
            ->whereHas('device', function (Builder $query) use ($unit) {
                if ($unit) {
                    $query->where('unit_id', $unit->id);
                }
            });
    }

    // AUTHORIZATION - HANYA USER YANG MEMILIKI UNIT
    public static function canViewAny(): bool
    {
        $user = Auth::user();
        return $user?->unit !== null && $user->isUnitUser();
    }

    public static function canCreate(): bool
    {
        $user = Auth::user();
        return $user?->unit !== null && $user->isUnitUser();
    }

    public static function canEdit($record): bool
    {
        $user = Auth::user();
        $unit = $user?->unit;
        if (!$unit || !$user->isUnitUser()) return false;
        
        return $record->device->unit_id === $unit->id;
    }

    public static function canDelete($record): bool
    {
        $user = Auth::user();
        $unit = $user?->unit;
        if (!$unit || !$user->isUnitUser()) return false;
        
        return $record->device->unit_id === $unit->id;
    }

    public static function canView($record): bool
    {
        $user = Auth::user();
        $unit = $user?->unit;
        if (!$unit || !$user->isUnitUser()) return false;
        
        return $record->device->unit_id === $unit->id;
    }

    // NAVIGATION BADGE - HITUNG DATA UNIT INI SAJA
    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();
        $unit = $user?->unit;
        if (!$unit) return null;
        
        $count = static::getEloquentQuery()->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}