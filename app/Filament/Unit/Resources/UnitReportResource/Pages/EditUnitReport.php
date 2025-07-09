<?php

namespace App\Filament\Unit\Resources\UnitReportResource\Pages;

use App\Filament\Unit\Resources\UnitReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditUnitReport extends EditRecord
{
    protected static string $resource = UnitReportResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Reset status jika ada perubahan konfigurasi laporan
        if ($this->record->status === 'completed') {
            // Check if critical fields changed
            $criticalFields = ['report_format', 'data_source', 'device_id', 'start_date', 'end_date'];
            
            foreach ($criticalFields as $field) {
                if (isset($data[$field]) && $data[$field] != $this->record->{$field}) {
                    $data['status'] = 'pending';
                    $data['file_path'] = null;
                    $data['generated_at'] = null;
                    
                    Notification::make()
                        ->title('Status Laporan Direset')
                        ->body('Karena ada perubahan konfigurasi, status laporan direset ke pending.')
                        ->warning()
                        ->send();
                    break;
                }
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Laporan berhasil diperbarui')
            ->body('Perubahan pada laporan telah disimpan.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url($this->getResource()::getUrl('view', ['record' => $this->record])),

            Actions\Action::make('generate')
                ->label('Generate Setelah Edit')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('primary')
                ->visible(fn () => $this->record->status === 'pending' || $this->record->status === 'failed')
                ->action(function () {
                    // Save first
                    $this->save();
                    
                    // Then generate
                    $this->record->update(['status' => 'processing']);
                    
                    Notification::make()
                        ->title('Laporan Disimpan dan Sedang Diproses')
                        ->success()
                        ->send();
                    
                    return redirect()->to('/unit-manage/reports/' . $this->record->id . '/generate');
                }),

            Actions\ViewAction::make(),

            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->status === 'pending' || $this->record->status === 'failed')
                ->successRedirectUrl($this->getResource()::getUrl('index')),

            Actions\Action::make('duplicate')
                ->label('Duplikasi')
                ->icon('heroicon-o-document-duplicate')
                ->color('info')
                ->action(function () {
                    $newReport = $this->record->replicate();
                    $newReport->name = $this->record->name . ' (Copy)';
                    $newReport->status = 'pending';
                    $newReport->file_path = null;
                    $newReport->generated_at = null;
                    $newReport->save();
                    
                    Notification::make()
                        ->title('Laporan Berhasil Diduplikasi')
                        ->success()
                        ->send();
                    
                    return redirect($this->getResource()::getUrl('edit', ['record' => $newReport]));
                }),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label('Simpan Perubahan'),
            
            Actions\Action::make('saveAndGenerate')
                ->label('Simpan & Generate')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('success')
                ->visible(fn () => $this->record->status === 'pending' || $this->record->status === 'failed')
                ->action(function () {
                    $this->save();
                    
                    $this->record->update(['status' => 'processing']);
                    
                    return redirect()->to('/unit-manage/reports/' . $this->record->id . '/generate');
                }),
            
            $this->getCancelFormAction()
                ->label('Batal')
                ->url($this->getResource()::getUrl('view', ['record' => $this->record])),
        ];
    }
}