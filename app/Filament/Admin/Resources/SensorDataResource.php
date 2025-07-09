<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SensorDataResource\Pages;
use App\Models\Device;
use App\Models\SensorData;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SensorDataResource extends Resource
{
    protected static ?string $model = SensorData::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Data Sensor';

    protected static ?string $modelLabel = 'Data Sensor';

    protected static ?string $pluralModelLabel = 'Data Sensor';

    protected static ?string $navigationGroup = 'Monitoring';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('device_id')
                    ->label('Perangkat')
                    ->relationship('device', 'name')
                    ->required(),

                Forms\Components\DateTimePicker::make('recorded_at')
                    ->label('Waktu Perekaman')
                    ->required(),

                Forms\Components\TextInput::make('flowrate')
                    ->label('Flowrate (l/s)')
                    ->numeric(),

                Forms\Components\TextInput::make('totalizer')
                    ->label('Totalizer (m³)')
                    ->numeric(),

                Forms\Components\TextInput::make('battery')
                    ->label('Baterai (Volt)')
                    ->numeric(),

                Forms\Components\TextInput::make('pressure1')
                    ->label('Tekanan 1 (bar)')
                    ->numeric(),

                Forms\Components\TextInput::make('pressure2')
                    ->label('Tekanan 2 (bar)')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('device.name')
                    ->label('Perangkat')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('recorded_at')
                    ->label('Waktu')
                    ->dateTime('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('flowrate')
                    ->label('Flowrate')
                    ->formatStateUsing(fn($state) => $state ? number_format($state, 5) . ' l/s' : 'N/A')
                    ->sortable(),
                Tables\Columns\TextColumn::make('totalizer')
                    ->label('Totalizer')
                    ->formatStateUsing(fn($state) => $state ? number_format($state, 5) . ' l/s' : 'N/A')
                    ->sortable(),

                Tables\Columns\TextColumn::make('battery')
                    ->label('Baterai')
                    ->formatStateUsing(fn($state) => $state ? number_format($state, 5) . ' Volt' : 'N/A')
                    ->sortable(),

                Tables\Columns\TextColumn::make('pressure1')
                    ->label('Tekanan 1')
                    ->formatStateUsing(fn($state) => $state ? number_format($state, 5) . ' bar' : 'N/A')
                    ->sortable(),

                Tables\Columns\TextColumn::make('pressure2')
                    ->label('Tekanan 2')
                    ->formatStateUsing(fn($state) => $state ? number_format($state, 5) . ' bar' : 'N/A')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('recorded_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('device_id')
                    ->label('Perangkat')
                    ->relationship('device', 'name')
                    ->multiple()
                    ->preload(),

                Tables\Filters\Filter::make('recorded_at')
                    ->form([
                        Forms\Components\DatePicker::make('recorded_from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('recorded_until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['recorded_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('recorded_at', '>=', $date),
                            )
                            ->when(
                                $data['recorded_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('recorded_at', '<=', $date),
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
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSensorData::route('/'),
            'create' => Pages\CreateSensorData::route('/create'),
            'edit' => Pages\EditSensorData::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Jika user adalah role 'unit', filter berdasarkan unit mereka
        if (Auth::user()->role === 'unit') {
            $query->whereHas('device', function ($q) {
                $q->where('unit_id', Auth::user()->unit_id);
            });
        }

        return $query;
    }
}
