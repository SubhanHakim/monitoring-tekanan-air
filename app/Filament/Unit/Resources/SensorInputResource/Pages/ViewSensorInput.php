<?php
// filepath: app/Filament/Unit/Resources/SensorInputResource/Pages/ViewSensorInput.php

namespace App\Filament\Unit\Resources\SensorInputResource\Pages;

use App\Filament\Unit\Resources\SensorInputResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSensorInput extends ViewRecord
{
    protected static string $resource = SensorInputResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'Detail Data Sensor';
    }
}