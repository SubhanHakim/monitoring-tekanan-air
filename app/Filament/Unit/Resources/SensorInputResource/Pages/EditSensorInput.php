<?php
// filepath: app/Filament/Unit/Resources/SensorInputResource/Pages/EditSensorInput.php

namespace App\Filament\Unit\Resources\SensorInputResource\Pages;

use App\Filament\Unit\Resources\SensorInputResource;
use App\Models\SensorData;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditSensorInput extends EditRecord
{
    protected static string $resource = SensorInputResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'Edit Data Sensor';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Validasi totalizer tidak boleh lebih kecil dari data sebelumnya
        // (kecuali data yang sedang diedit)
        if (isset($data['device_id']) && isset($data['totalizer'])) {
            $lastData = SensorData::where('device_id', $data['device_id'])
                ->where('id', '!=', $this->record->id)
                ->orderBy('recorded_at', 'desc')
                ->first();

            if ($lastData && $data['totalizer'] < $lastData->totalizer) {
                Notification::make()
                    ->title('Validasi Error')
                    ->body("Totalizer tidak boleh lebih kecil dari data sebelumnya ({$lastData->totalizer} L)")
                    ->danger()
                    ->send();
                
                $this->halt();
            }
        }

        return $data;
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Berhasil')
            ->body('Data sensor berhasil diperbarui');
    }
}