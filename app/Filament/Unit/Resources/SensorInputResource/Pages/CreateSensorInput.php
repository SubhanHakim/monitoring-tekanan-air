<?php
// filepath: app/Filament/Unit/Resources/SensorInputResource/Pages/CreateSensorInput.php

namespace App\Filament\Unit\Resources\SensorInputResource\Pages;

use App\Filament\Unit\Resources\SensorInputResource;
use App\Models\Device;
use App\Models\SensorData;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateSensorInput extends CreateRecord
{
    protected static string $resource = SensorInputResource::class;

    public function getTitle(): string
    {
        return 'Input Data Sensor Baru';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();
        $unit = $user?->unit;

        // VALIDASI UNIT
        if (!$unit) {
            Notification::make()
                ->title('Error')
                ->body('Anda tidak terhubung ke unit apapun')
                ->danger()
                ->send();
            $this->halt();
        }

        // VALIDASI DEVICE MILIK UNIT INI
        if (isset($data['device_id'])) {
            $device = Device::where('id', $data['device_id'])
                           ->where('unit_id', $unit->id)
                           ->first();

            if (!$device) {
                Notification::make()
                    ->title('Error')
                    ->body('Perangkat tidak ditemukan atau tidak memiliki akses')
                    ->danger()
                    ->send();
                $this->halt();
            }

            // VALIDASI TOTALIZER
            if (isset($data['totalizer'])) {
                $lastData = SensorData::where('device_id', $data['device_id'])
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
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $record = static::getModel()::create($data);

        Notification::make()
            ->title('Berhasil')
            ->body('Data sensor berhasil disimpan untuk unit Anda')
            ->success()
            ->send();

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}