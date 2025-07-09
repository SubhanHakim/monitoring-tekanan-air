<?php

namespace App\Filament\Unit\Resources\UnitGeneratedReportResource\Pages;

use App\Filament\Unit\Resources\UnitGeneratedReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewUnitGeneratedReport extends ViewRecord
{
    protected static string $resource = UnitGeneratedReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}