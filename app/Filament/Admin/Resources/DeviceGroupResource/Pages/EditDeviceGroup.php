<?php

namespace App\Filament\Admin\Resources\DeviceGroupResource\Pages;

use App\Filament\Admin\Resources\DeviceGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDeviceGroup extends EditRecord
{
    protected static string $resource = DeviceGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
