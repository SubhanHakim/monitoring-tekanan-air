<?php
// filepath: app/Filament/Admin/Resources/DeviceResource.php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\DeviceResource\Pages;
use App\Models\Device;
use App\Models\Unit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DeviceResource extends Resource
{
    protected static ?string $model = Device::class;

    protected static ?string $navigationIcon = 'heroicon-o-device-tablet';

    protected static ?string $navigationLabel = 'Perangkat IOT';

    protected static ?string $modelLabel = 'Perangkat';

    protected static ?string $pluralModelLabel = 'Perangkat';

    protected static ?string $navigationGroup = 'Manajemen Perangkat';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Dasar Perangkat')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Kode Perangkat')
                            ->unique(ignoreRecord: true)
                            ->placeholder('Otomatis jika kosong')
                            ->helperText('Kode unik perangkat (opsional)'),

                        Forms\Components\TextInput::make('name')
                            ->label('Nama Perangkat')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Sensor Tekanan Unit A'),

                        Forms\Components\Select::make('device_type')
                            ->label('Tipe Perangkat')
                            ->options([
                                'pressure_sensor' => 'Sensor Tekanan',
                                'flow_meter' => 'Flow Meter',
                                'water_level' => 'Sensor Level Air',
                                'temperature_sensor' => 'Sensor Suhu',
                                'ph_sensor' => 'Sensor pH',
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('unit_id')
                            ->label('Unit Lokasi')
                            ->relationship('unit', 'name')
                            ->options(
                                fn() => Unit::active()->pluck('name', 'id')
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->placeholder('Pilih unit lokasi perangkat')
                            ->helperText('Pilih unit tempat perangkat ini dipasang'),

                        Forms\Components\TextInput::make('location')
                            ->label('Lokasi Detail')
                            ->maxLength(255)
                            ->placeholder('Contoh: Ruang Pompa Lt. 2')
                            ->helperText('Lokasi spesifik dalam unit'),
                    ])->columns(2),

                Forms\Components\Section::make('Spesifikasi Teknis')
                    ->schema([
                        Forms\Components\TextInput::make('merek')
                            ->label('Merek/Brand')
                            ->maxLength(255)
                            ->placeholder('Contoh: Schneider Electric'),

                        Forms\Components\TextInput::make('diameter')
                            ->label('Diameter')
                            ->maxLength(255)
                            ->placeholder('Contoh: 2 inch')
                            ->helperText('Diameter pipa/sensor (jika ada)'),

                        Forms\Components\Select::make('jenis_distribusi')
                            ->label('Jenis Distribusi')
                            ->options([
                                'primer' => 'Primer',
                                'sekunder' => 'Sekunder',
                                'tersier' => 'Tersier',
                                'distribusi' => 'Distribusi',
                                'transmisi' => 'Transmisi',
                            ])
                            ->native(false)
                            ->placeholder('Pilih jenis distribusi'),

                        Forms\Components\FileUpload::make('image_perangkat')
                            ->label('Foto Perangkat')
                            ->image()
                            ->directory('devices')
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->helperText('Upload foto perangkat (max 2MB)'),
                    ])->columns(2),

                Forms\Components\Section::make('Status & Setting')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'active' => 'Aktif',
                                'inactive' => 'Tidak Aktif',
                                'maintenance' => 'Dalam Perawatan',
                                'error' => 'Error',
                                'offline' => 'Offline',
                                'baik' => 'Baik',
                                'rusak' => 'Rusak',
                            ])
                            ->default('active')
                            ->native(false)
                            ->required(),

                        Forms\Components\TextInput::make('api_key')
                            ->label('API Key')
                            ->maxLength(255)
                            ->placeholder('API Key untuk komunikasi')
                            ->password()
                            ->revealable()
                            ->helperText('Kunci API untuk koneksi perangkat'),

                        Forms\Components\DateTimePicker::make('last_active_at')
                            ->label('Terakhir Aktif')
                            ->nullable()
                            ->displayFormat('d/m/Y H:i'),
                    ])->columns(2),

                Forms\Components\Section::make('Konfigurasi Lanjutan')
                    ->schema([
                        Forms\Components\KeyValue::make('configuration')
                            ->label('Konfigurasi Perangkat')
                            ->keyLabel('Parameter')
                            ->valueLabel('Nilai')
                            ->addActionLabel('Tambah Parameter')
                            ->deletable()
                            ->nullable()
                            ->helperText('Konfigurasi khusus untuk perangkat ini'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('N/A'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Perangkat')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),

                Tables\Columns\TextColumn::make('device_type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'pressure_sensor' => 'Sensor Tekanan',
                        'flow_meter' => 'Flow Meter',
                        'water_level' => 'Level Air',
                        'temperature_sensor' => 'Sensor Suhu',
                        'ph_sensor' => 'Sensor pH',
                        default => $state,
                    })
                    ->colors([
                        'primary' => 'pressure_sensor',
                        'success' => 'flow_meter',
                        'warning' => 'water_level',
                        'info' => 'temperature_sensor',
                        'secondary' => 'ph_sensor',
                    ]),

                Tables\Columns\TextColumn::make('unit.name')
                    ->label('Unit')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Unit tidak ada'),

                Tables\Columns\TextColumn::make('location')
                    ->label('Lokasi Detail')
                    ->searchable()
                    ->limit(30)
                    ->placeholder('Tidak ada'),

                Tables\Columns\TextColumn::make('merek')
                    ->label('Merek')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('N/A'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'active' => 'Aktif',
                        'inactive' => 'Tidak Aktif',
                        'maintenance' => 'Maintenance',
                        'error' => 'Error',
                        'offline' => 'Offline',
                        'baik' => 'Baik',
                        'rusak' => 'Rusak',
                        default => $state,
                    })
                    ->colors([
                        'success' => ['active', 'baik'],
                        'danger' => ['inactive', 'error', 'rusak'],
                        'warning' => ['maintenance', 'offline'],
                    ]),

                Tables\Columns\TextColumn::make('last_active_at')
                    ->label('Terakhir Aktif')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('Belum pernah')
                    ->color(function ($state) {
                        if (!$state) return 'gray';
                        return $state->diffInHours(now()) < 24 ? 'success' : 'danger';
                    }),

                Tables\Columns\ImageColumn::make('image_perangkat')
                    ->label('Foto')
                    ->circular()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('device_type')
                    ->label('Tipe Perangkat')
                    ->options([
                        'pressure_sensor' => 'Sensor Tekanan',
                        'flow_meter' => 'Flow Meter',
                        'water_level' => 'Sensor Level Air',
                        'temperature_sensor' => 'Sensor Suhu',
                        'ph_sensor' => 'Sensor pH',
                    ])
                    ->native(false),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Aktif',
                        'inactive' => 'Tidak Aktif',
                        'maintenance' => 'Maintenance',
                        'error' => 'Error',
                        'offline' => 'Offline',
                        'baik' => 'Baik',
                        'rusak' => 'Rusak',
                    ])
                    ->native(false),

                Tables\Filters\SelectFilter::make('unit_id')
                    ->label('Unit')
                    ->relationship('unit', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('jenis_distribusi')
                    ->label('Jenis Distribusi')
                    ->options([
                        'primer' => 'Primer',
                        'sekunder' => 'Sekunder',
                        'tersier' => 'Tersier',
                        'distribusi' => 'Distribusi',
                        'transmisi' => 'Transmisi',
                    ])
                    ->native(false),

                Tables\Filters\Filter::make('last_active_at')
                    ->label('Status Aktivitas')
                    ->form([
                        Forms\Components\DatePicker::make('active_from')
                            ->label('Aktif Dari'),
                        Forms\Components\DatePicker::make('active_until')
                            ->label('Aktif Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['active_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('last_active_at', '>=', $date)
                            )
                            ->when(
                                $data['active_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('last_active_at', '<=', $date)
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Lihat'),
                Tables\Actions\EditAction::make()->label('Edit'),
                Tables\Actions\DeleteAction::make()->label('Hapus'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih'),
                    
                    Tables\Actions\BulkAction::make('assignToUnit')
                        ->label('Pindahkan ke Unit')
                        ->icon('heroicon-o-building-office')
                        ->form([
                            Forms\Components\Select::make('unit_id')
                                ->label('Pilih Unit')
                                ->options(Unit::active()->pluck('name', 'id'))
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (array $records, array $data) {
                            foreach ($records as $record) {
                                $record->update(['unit_id' => $data['unit_id']]);
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Pindahkan Perangkat ke Unit Lain')
                        ->modalDescription('Apakah Anda yakin ingin memindahkan perangkat terpilih ke unit yang baru?')
                        ->modalSubmitActionLabel('Pindahkan'),

                    Tables\Actions\BulkAction::make('updateStatus')
                        ->label('Ubah Status')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('Status Baru')
                                ->options([
                                    'active' => 'Aktif',
                                    'inactive' => 'Tidak Aktif',
                                    'maintenance' => 'Maintenance',
                                    'error' => 'Error',
                                    'offline' => 'Offline',
                                    'baik' => 'Baik',
                                    'rusak' => 'Rusak',
                                ])
                                ->required(),
                        ])
                        ->action(function (array $records, array $data) {
                            foreach ($records as $record) {
                                $record->update(['status' => $data['status']]);
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Ubah Status Perangkat')
                        ->modalDescription('Apakah Anda yakin ingin mengubah status perangkat terpilih?')
                        ->modalSubmitActionLabel('Ubah Status'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Belum ada perangkat')
            ->emptyStateDescription('Mulai dengan menambahkan perangkat IoT pertama.')
            ->emptyStateIcon('heroicon-o-device-tablet');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Filter berdasarkan role user
        if (auth()->user()->role === 'unit' && auth()->user()->unit_id) {
            $query->where('unit_id', auth()->user()->unit_id);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDevices::route('/'),
            'create' => Pages\CreateDevice::route('/create'),
            // 'view' => Pages\ViewDevice::route('/{record}'),
            'edit' => Pages\EditDevice::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $query = static::getEloquentQuery();
        return $query->count();
    }
}