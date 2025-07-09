<?php

namespace App\Filament\Unit\Resources\UnitReportResource\Pages;

use App\Filament\Unit\Resources\UnitReportResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class CreateUnitReport extends CreateRecord
{
    protected static string $resource = UnitReportResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();
        
        // ✅ ENSURE SEMUA REQUIRED FIELDS ADA
        $data['unit_id'] = $user->unit_id ?? null;  // ✅ NULLABLE in migration
        $data['created_by'] = $user->id;            // ✅ REQUIRED in migration
        
        // ✅ ENSURE name ada (required in migration)
        if (empty($data['name'])) {
            $data['name'] = 'Laporan Monitoring ' . now()->format('d/m/Y H:i');
        }
        
        // ✅ ENSURE dates ada (required in migration)
        if (empty($data['start_date'])) {
            $data['start_date'] = now()->subDays(7)->format('Y-m-d');
        }
        
        if (empty($data['end_date'])) {
            $data['end_date'] = now()->format('Y-m-d');
        }
        
        // ✅ ENSURE enum fields ada dengan default values
        $data['status'] = $data['status'] ?? 'pending';
        $data['report_format'] = $data['report_format'] ?? 'summary';
        $data['data_source'] = $data['data_source'] ?? 'all';
        $data['file_type'] = $data['file_type'] ?? 'pdf';
        
        // ✅ SET nullable fields
        $data['description'] = $data['description'] ?? null;
        $data['device_id'] = $data['device_id'] ?? null;
        $data['device_group_id'] = $data['device_group_id'] ?? null;
        $data['metrics'] = $data['metrics'] ?? null;
        $data['file_path'] = $data['file_path'] ?? null;
        $data['generated_at'] = $data['generated_at'] ?? null;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Laporan berhasil dibuat')
            ->body('Laporan telah dibuat dengan status pending. Anda dapat generate laporan sekarang.');
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url($this->getResource()::getUrl('index')),
        ];
    }
}