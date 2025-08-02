<?php
// filepath: app/Filament/Admin/Resources/UnitProfileResource/Pages/ListUnitProfiles.php

namespace App\Filament\Admin\Resources\UnitProfileResource\Pages;

use App\Filament\Admin\Resources\UnitProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUnitProfiles extends ListRecords
{
    protected static string $resource = UnitProfileResource::class;

    public function getTitle(): string
    {
        return 'Kelola Unit Kerja';
    }

    public function getHeading(): string
    {
        return 'Kelola Unit Kerja';
    }

    public function getSubheading(): ?string
    {
        return 'Manajemen semua unit kerja dalam sistem';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Unit')
                ->icon('heroicon-o-plus'),
        ];
    }
}