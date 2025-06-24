<?php

namespace App\Filament\Admin\Resources\DeviceGroupResource\Pages;

use App\Filament\Admin\Resources\DeviceGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDeviceGroups extends ListRecords
{
    protected static string $resource = DeviceGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
