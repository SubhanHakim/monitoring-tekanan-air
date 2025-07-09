<?php

namespace App\Filament\Unit\Resources\UnitGeneratedReportResource\Pages;

use App\Filament\Unit\Resources\UnitGeneratedReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUnitGeneratedReport extends EditRecord
{
    protected static string $resource = UnitGeneratedReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}