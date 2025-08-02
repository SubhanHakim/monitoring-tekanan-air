<?php
// filepath: app/Filament/Admin/Resources/UnitProfileResource/Pages/EditUnitProfile.php

namespace App\Filament\Admin\Resources\UnitProfileResource\Pages;

use App\Filament\Admin\Resources\UnitProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUnitProfile extends EditRecord
{
    protected static string $resource = UnitProfileResource::class;

    public function getTitle(): string
    {
        return 'Edit Unit: ' . $this->record->name;
    }

    public function getHeading(): string
    {
        return 'Edit Unit Kerja';
    }

    public function getSubheading(): ?string
    {
        return 'Perbarui informasi unit kerja: ' . $this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('Lihat Unit'),
            Actions\DeleteAction::make()
                ->label('Hapus Unit'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Unit kerja berhasil diperbarui!';
    }
}