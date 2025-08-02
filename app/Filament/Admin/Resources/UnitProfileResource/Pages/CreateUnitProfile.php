<?php
// filepath: app/Filament/Admin/Resources/UnitProfileResource/Pages/CreateUnitProfile.php

namespace App\Filament\Admin\Resources\UnitProfileResource\Pages;

use App\Filament\Admin\Resources\UnitProfileResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUnitProfile extends CreateRecord
{
    protected static string $resource = UnitProfileResource::class;

    public function getTitle(): string
    {
        return 'Tambah Unit Kerja';
    }

    public function getHeading(): string
    {
        return 'Tambah Unit Kerja Baru';
    }

    public function getSubheading(): ?string
    {
        return 'Buat unit kerja baru dalam sistem';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Unit kerja berhasil dibuat!';
    }
}