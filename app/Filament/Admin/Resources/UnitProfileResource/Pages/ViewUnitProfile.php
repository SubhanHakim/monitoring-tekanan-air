<?php
// filepath: app/Filament/Admin/Resources/UnitProfileResource/Pages/ViewUnitProfile.php

namespace App\Filament\Admin\Resources\UnitProfileResource\Pages;

use App\Filament\Admin\Resources\UnitProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewUnitProfile extends ViewRecord
{
    protected static string $resource = UnitProfileResource::class;

    public function getTitle(): string
    {
        return 'Detail Unit: ' . $this->record->name;
    }

    public function getHeading(): string
    {
        return 'Detail Unit Kerja';
    }

    public function getSubheading(): ?string
    {
        return 'Informasi lengkap unit kerja: ' . $this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit Unit'),
        ];
    }
}