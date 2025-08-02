<?php
// filepath: app/Filament/Admin/Resources/DeviceGroupResource.php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\DeviceGroupResource\Pages;
use App\Filament\Admin\Resources\DeviceGroupResource\RelationManagers;
use App\Models\DeviceGroup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DeviceGroupResource extends Resource
{
    protected static ?string $model = DeviceGroup::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    
    protected static ?string $navigationLabel = 'Grup Perangkat';
    
    protected static ?string $modelLabel = 'Grup Perangkat';
    
    protected static ?string $pluralModelLabel = 'Grup Perangkat';
    
    protected static ?string $navigationGroup = 'Manajemen Perangkat';
    
    protected static ?int $navigationSort = 2;

    // ✅ TAMBAHKAN INI UNTUK HIDE DARI NAVIGATION
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Grup')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Grup')
                            ->required()
                            ->maxLength(255),
                            
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
                            ->label('Deskripsi')
                            ->maxLength(500)
                            ->columnSpanFull(),
                            
                        Forms\Components\ColorPicker::make('color')
                            ->label('Warna')
                            ->rgba(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Grup')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe Grup')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'location' => 'Lokasi',
                        'division' => 'Divisi',
                        'project' => 'Proyek',
                        'custom' => 'Kustom',
                        default => $state,
                    })
                    ->colors([
                        'primary' => 'location',
                        'success' => 'division',
                        'warning' => 'project',
                        'gray' => 'custom',
                    ]),
                    
                Tables\Columns\ColorColumn::make('color')
                    ->label('Warna'),
                    
                Tables\Columns\TextColumn::make('devices_count')
                    ->label('Jumlah Perangkat')
                    ->counts('devices')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe Grup')
                    ->options([
                        'location' => 'Lokasi',
                        'division' => 'Divisi',
                        'project' => 'Proyek',
                        'custom' => 'Kustom',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            RelationManagers\DevicesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeviceGroups::route('/'),
            'create' => Pages\CreateDeviceGroup::route('/create'),
            'edit' => Pages\EditDeviceGroup::route('/{record}/edit'),
        ];
    }
}