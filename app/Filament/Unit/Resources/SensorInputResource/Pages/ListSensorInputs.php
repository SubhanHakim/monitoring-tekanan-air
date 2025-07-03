<?php
// filepath: app/Filament/Unit/Resources/SensorInputResource/Pages/ListSensorInputs.php

namespace App\Filament\Unit\Resources\SensorInputResource\Pages;

use App\Filament\Unit\Resources\SensorInputResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSensorInputs extends ListRecords
{
    protected static string $resource = SensorInputResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Input Data Baru')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTitle(): string
    {
        return 'Data Sensor';
    }
}