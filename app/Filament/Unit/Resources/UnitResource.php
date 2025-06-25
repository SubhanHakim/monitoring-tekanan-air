<?php

namespace App\Filament\Unit\Resources;

use App\Filament\Unit\Resources\UnitResource\Pages\CreateUnit;
use App\Filament\Unit\Resources\UnitResource\Pages\EditUnit;
use App\Filament\Unit\Resources\UnitResource\Pages\ListUnits;
use App\Models\Unit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Unit Pengguna';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Unit')
                    ->required()
                    ->maxLength(255),
                    
                Forms\Components\TextInput::make('location')
                    ->label('Lokasi')
                    ->maxLength(255),
                    
                Forms\Components\Textarea::make('description')
                    ->label('Deskripsi')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                    
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Aktif',
                        'inactive' => 'Tidak Aktif',
                        'maintenance' => 'Pemeliharaan',
                    ])
                    ->default('active')
                    ->required(),
                    
                Forms\Components\Section::make('Pengguna Unit')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Pengguna')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('password')
                                    ->label('Password')
                                    ->password()
                                    ->required()
                                    ->dehydrateStateUsing(fn ($state) => bcrypt($state))
                                    ->maxLength(255),
                            ])
                    ]),
                    
                Forms\Components\Section::make('Perangkat Unit')
                    ->schema([
                        Forms\Components\Repeater::make('devices')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Perangkat')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('location')
                                    ->label('Lokasi')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('api_key')
                                    ->label('API Key')
                                    ->default(fn () => \Illuminate\Support\Str::random(32))
                                    ->maxLength(255),
                            ])
                            ->defaultItems(0)
                            ->reorderable(false)
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Unit')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('location')
                    ->label('Lokasi')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pengguna')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('devices_count')
                    ->label('Jumlah Perangkat')
                    ->counts('devices')
                    ->sortable(),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                        'warning' => 'maintenance',
                    ]),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Aktif',
                        'inactive' => 'Tidak Aktif',
                        'maintenance' => 'Pemeliharaan',
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUnits::route('/'),
            'create' => CreateUnit::route('/create'),
            'edit' => EditUnit::route('/{record}/edit'),
        ];
    }
}