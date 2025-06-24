<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\DeviceResource\Pages;
use App\Models\Device;
use App\Models\DeviceGroup;
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
                Forms\Components\Section::make('Informasi Perangkat')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Perangkat')
                            ->required()
                            ->maxLength(255),
                            
                        Forms\Components\Select::make('device_type')
                            ->label('Tipe Perangkat')
                            ->options([
                                'pressure_sensor' => 'Sensor Tekanan',
                                'flow_meter' => 'Flow Meter',
                                'water_level' => 'Sensor Level Air',
                            ])
                            ->required(),
                            
                        Forms\Components\TextInput::make('location')
                            ->label('Lokasi')
                            ->required()
                            ->maxLength(255),
                            
                        // Tambahkan field untuk grup perangkat
                        Forms\Components\Select::make('device_group_id')
                            ->label('Grup Perangkat')
                            ->relationship('group', 'name')
                            ->options(
                                fn () => DeviceGroup::all()->pluck('name', 'id')
                            )
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Grup')
                                    ->required(),
                                Forms\Components\Select::make('type')
                                    ->label('Tipe Grup')
                                    ->options([
                                        'location' => 'Lokasi',
                                        'division' => 'Divisi',
                                        'project' => 'Proyek',
                                        'custom' => 'Kustom',
                                    ])
                                    ->required(),
                                Forms\Components\Textarea::make('description')
                                    ->label('Deskripsi'),
                                Forms\Components\ColorPicker::make('color')
                                    ->label('Warna'),
                            ]),
                            
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'active' => 'Aktif',
                                'inactive' => 'Tidak Aktif',
                                'maintenance' => 'Dalam Perawatan',
                                'error' => 'Error',
                            ])
                            ->default('active'),
                            
                        Forms\Components\DateTimePicker::make('last_active_at')
                            ->label('Terakhir Aktif')
                            ->nullable(),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Konfigurasi')
                    ->schema([
                        Forms\Components\KeyValue::make('configuration')
                            ->label('Konfigurasi Perangkat')
                            ->keyLabel('Parameter')
                            ->valueLabel('Nilai')
                            ->addActionLabel('Tambah Parameter')
                            ->deletable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Perangkat')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('device_type')
                    ->label('Tipe Perangkat')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pressure_sensor' => 'Sensor Tekanan',
                        'flow_meter' => 'Flow Meter',
                        'water_level' => 'Sensor Level Air',
                        default => $state,
                    })
                    ->colors([
                        'primary' => 'pressure_sensor',
                        'success' => 'flow_meter',
                        'warning' => 'water_level',
                    ]),
                    
                Tables\Columns\TextColumn::make('location')
                    ->label('Lokasi')
                    ->searchable()
                    ->sortable(),
                    
                // Tambahkan kolom untuk grup perangkat
                Tables\Columns\TextColumn::make('group.name')
                    ->label('Grup')
                    ->badge()
                    ->color(fn ($record) => $record->group?->color ?? 'gray')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Aktif',
                        'inactive' => 'Tidak Aktif',
                        'maintenance' => 'Dalam Perawatan',
                        'error' => 'Error',
                        default => $state,
                    })
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                        'warning' => 'maintenance',
                        'danger' => 'error',
                    ]),
                    
                Tables\Columns\TextColumn::make('last_active_at')
                    ->label('Terakhir Aktif')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Dibuat')
                    ->dateTime('d M Y')
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
                    ]),
                    
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Aktif',
                        'inactive' => 'Tidak Aktif',
                        'maintenance' => 'Dalam Perawatan',
                        'error' => 'Error',
                    ]),
                    
                // Tambahkan filter untuk grup perangkat
                Tables\Filters\SelectFilter::make('device_group_id')
                    ->label('Grup')
                    ->relationship('group', 'name'),
                    
                Tables\Filters\Filter::make('last_active_at')
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
                                fn (Builder $query, $date): Builder => $query->whereDate('last_active_at', '>=', $date),
                            )
                            ->when(
                                $data['active_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('last_active_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    // Tambahkan bulk action untuk mengubah grup perangkat
                    Tables\Actions\BulkAction::make('assignToGroup')
                        ->label('Pindahkan ke Grup')
                        ->form([
                            Forms\Components\Select::make('device_group_id')
                                ->label('Pilih Grup')
                                ->options(DeviceGroup::all()->pluck('name', 'id'))
                                ->required(),
                        ])
                        ->action(function (array $records, array $data) {
                            foreach ($records as $record) {
                                $record->update([
                                    'device_group_id' => $data['device_group_id'],
                                ]);
                            }
                        }),
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
            'index' => Pages\ListDevices::route('/'),
            'create' => Pages\CreateDevice::route('/create'),
            'edit' => Pages\EditDevice::route('/{record}/edit'),
        ];
    }
}