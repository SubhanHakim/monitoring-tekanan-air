<?php

namespace App\Filament\Admin\Resources\ReportResource\Pages;

use App\Filament\Admin\Resources\ReportResource;
use App\Models\Report;
use App\Services\ReportService;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Storage;

class ViewReport extends Page
{
    protected static string $resource = ReportResource::class;

    protected static string $view = 'filament.admin.resources.report-resource.pages.view-report';

    public ?Report $record = null;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        // Redirect ke halaman index jika laporan tidak ditemukan
        if (!$this->record) {
            $this->redirect(static::getResource()::getUrl('index'));
            return;
        }
    }

    protected function resolveRecord(int|string $key): ?Report
    {
        return Report::find($key);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Kembali')
                ->url(static::getResource()::getUrl('index'))
                ->icon('heroicon-o-arrow-left'),

            Action::make('download')
                ->label('Download PDF')
                ->icon('heroicon-s-arrow-down-tray')
                ->url(fn() => $this->record->last_generated_file ? route('reports.download', $this->record) : null)
                ->openUrlInNewTab()
                ->visible(fn() => $this->record->last_generated_file && Storage::exists($this->record->last_generated_file)),

            Action::make('regenerate')
                ->label('Generate Ulang')
                ->icon('heroicon-s-arrow-path')
                ->action(function () {
                    $reportService = app(ReportService::class);
                    $data = $reportService->generateReport($this->record);
                    $filePath = $reportService->generatePDF($this->record, $data);

                    $this->notify('success', 'Laporan berhasil digenerate ulang');
                    // Refresh halaman untuk menampilkan perubahan
                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                })
                ->requiresConfirmation(),
        ];
    }
}