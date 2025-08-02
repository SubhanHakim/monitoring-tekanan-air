<?php
// filepath: app/Filament/Admin/Resources/UnitProfileResource.php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UnitProfileResource\Pages;
use App\Models\Unit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class UnitProfileResource extends Resource
{
    protected static ?string $model = Unit::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Kelola Unit';

    protected static ?string $modelLabel = 'Unit';

    protected static ?string $pluralModelLabel = 'Unit';

    protected static ?string $navigationGroup = 'Manajemen Perangkat';

    protected static ?int $navigationSort = 2;

    // ✅ ADMIN BISA LIHAT SEMUA UNIT (remove restriction)
    // public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    // {
    //     return parent::getEloquentQuery(); // Admin sees all units
    // }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Unit')
                    ->description('Kelola data unit kerja')
                    ->icon('heroicon-o-building-office-2')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Unit')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Unit Cikarang')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('location')
                            ->label('Lokasi')
                            ->maxLength(255)
                            ->placeholder('Contoh: Jl. Raya Cikarang No. 123')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->placeholder('Deskripsi singkat tentang unit kerja ini...')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('koordinate')
                            ->label('Koordinat')
                            ->maxLength(255)
                            ->placeholder('Contoh: -6.2088, 106.8456')
                            ->helperText('Format: latitude, longitude'),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'active' => 'Aktif',
                                'inactive' => 'Tidak Aktif',
                                'maintenance' => 'Maintenance',
                            ])
                            ->default('active')
                            ->required()
                            ->native(false),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Unit')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-building-office-2')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('location')
                    ->label('Lokasi')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('koordinate')
                    ->label('Koordinat')
                    ->toggleable()
                    ->icon('heroicon-o-map-pin'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                        'warning' => 'maintenance',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Aktif',
                        'inactive' => 'Tidak Aktif',
                        'maintenance' => 'Maintenance',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('users_count')
                    ->label('Jumlah User')
                    ->counts('users')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Aktif',
                        'inactive' => 'Tidak Aktif',
                        'maintenance' => 'Maintenance',
                    ])
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat'),
                Tables\Actions\EditAction::make()
                    ->label('Edit'),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Belum ada unit kerja')
            ->emptyStateDescription('Mulai dengan membuat unit kerja pertama.')
            ->emptyStateIcon('heroicon-o-building-office-2');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUnitProfiles::route('/'),
            'create' => Pages\CreateUnitProfile::route('/create'),
            'view' => Pages\ViewUnitProfile::route('/{record}'),
            'edit' => Pages\EditUnitProfile::route('/{record}/edit'),
        ];
    }

    // ✅ ENABLE SEMUA CRUD OPERATIONS untuk Admin
    public static function canCreate(): bool
    {
        return true;
    }

    public static function canEdit($record): bool
    {
        return true;
    }

    public static function canDelete($record): bool
    {
        return true;
    }

    public static function canDeleteAny(): bool
    {
        return true;
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::getModel()::count() > 10 ? 'warning' : 'primary';
    }
}