<?php

namespace App\Filament\Unit\Resources\UnitGeneratedReportResource\Pages;

use App\Filament\Unit\Resources\UnitGeneratedReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUnitGeneratedReports extends ListRecords
{
    protected static string $resource = UnitGeneratedReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}