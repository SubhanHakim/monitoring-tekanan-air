<?php

namespace App\Filament\Unit\Resources\UnitReportResource\Pages;

use App\Filament\Unit\Resources\UnitReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Storage;

class ViewUnitReport extends ViewRecord
{
    protected static string $resource = UnitReportResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Laporan')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('Nama Laporan')
                            ->weight('bold')
                            ->size('lg'),

                        Infolists\Components\TextEntry::make('description')
                            ->label('Deskripsi')
                            ->placeholder('Tidak ada deskripsi'),

                        Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'processing' => 'info',
                                'completed' => 'success',
                                'failed' => 'danger',
                            })
                            ->formatStateUsing(fn (string $state): string => match($state) {
                                'pending' => '⏳ Pending',
                                'processing' => '⚙️ Memproses',
                                'completed' => '✅ Selesai',
                                'failed' => '❌ Gagal',
                                default => $state,
                            }),

                        Infolists\Components\TextEntry::make('report_format')
                            ->label('Format Laporan')
                            ->formatStateUsing(fn (string $state): string => match($state) {
                                'summary' => '📊 Ringkasan',
                                'detailed' => '📋 Detail',
                                'statistical' => '📈 Statistik',
                                default => $state,
                            }),

                        Infolists\Components\TextEntry::make('file_type')
                            ->label('Tipe File')
                            ->formatStateUsing(fn (string $state): string => strtoupper($state)),

                        Infolists\Components\TextEntry::make('data_source')
                            ->label('Sumber Data')
                            ->formatStateUsing(fn (string $state): string => match($state) {
                                'all' => '🏢 Semua Perangkat',
                                'device' => '📱 Perangkat Spesifik',
                                default => $state,
                            }),
                    ])->columns(2),

                Infolists\Components\Section::make('Detail Periode & Perangkat')
                    ->schema([
                        Infolists\Components\TextEntry::make('start_date')
                            ->label('Tanggal Mulai')
                            ->date('d F Y'),

                        Infolists\Components\TextEntry::make('end_date')
                            ->label('Tanggal Akhir')
                            ->date('d F Y'),

                        Infolists\Components\TextEntry::make('device.name')
                            ->label('Perangkat')
                            ->placeholder('Semua Perangkat')
                            ->visible(fn () => $this->record->data_source === 'device'),

                        Infolists\Components\TextEntry::make('file_path')
                            ->label('File Laporan')
                            ->visible(fn () => $this->record->status === 'completed' && $this->record->file_path)
                            ->formatStateUsing(fn (string $state): string => 
                                '📄 ' . basename($state)
                            ),
                    ])->columns(2),

                Infolists\Components\Section::make('Informasi Pembuatan')
                    ->schema([
                        Infolists\Components\TextEntry::make('createdBy.name')
                            ->label('Dibuat oleh'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Dibuat pada')
                            ->dateTime('d F Y, H:i'),

                        Infolists\Components\TextEntry::make('generated_at')
                            ->label('Digenerate pada')
                            ->dateTime('d F Y, H:i')
                            ->placeholder('Belum digenerate')
                            ->visible(fn () => $this->record->generated_at),

                        Infolists\Components\TextEntry::make('unit.name')
                            ->label('Unit'),
                    ])->columns(2),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url($this->getResource()::getUrl('index')),

            Actions\Action::make('generate')
                ->label('Generate Laporan')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('primary')
                ->visible(fn () => $this->record->status === 'pending' || $this->record->status === 'failed')
                ->requiresConfirmation()
                ->modalHeading('Generate Laporan')
                ->modalDescription('Apakah Anda yakin ingin generate laporan ini? Proses ini mungkin memakan waktu beberapa menit.')
                ->modalSubmitActionLabel('Ya, Generate')
                ->action(function () {
                    try {
                        $this->record->update([
                            'status' => 'processing',
                            'generated_at' => now()
                        ]);
                        
                        Notification::make()
                            ->title('Laporan Sedang Diproses')
                            ->body('Laporan sedang diproses, silakan tunggu beberapa saat...')
                            ->info()
                            ->persistent()
                            ->send();
                        
                        // Redirect ke generate route untuk proses async
                        return redirect()->to('/unit-manage/reports/' . $this->record->id . '/generate');
                        
                    } catch (\Exception $e) {
                        $this->record->update(['status' => 'failed']);
                        
                        Notification::make()
                            ->title('Gagal Generate Laporan')
                            ->body('Terjadi kesalahan: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('regenerate')
                ->label('Generate Ulang')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn () => $this->record->status === 'completed')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update([
                        'status' => 'processing',
                        'generated_at' => now()
                    ]);
                    
                    Notification::make()
                        ->title('Regenerating Laporan')
                        ->info()
                        ->send();
                    
                    return redirect()->to('/unit-manage/reports/' . $this->record->id . '/generate');
                }),

            Actions\Action::make('download')
                ->label('Download')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn () => $this->record->status === 'completed' && $this->record->file_path)
                ->action(function () {
                    if (!$this->record->file_path || !Storage::exists($this->record->file_path)) {
                        Notification::make()
                            ->title('File Tidak Ditemukan')
                            ->body('File laporan tidak ditemukan. Silakan generate ulang.')
                            ->danger()
                            ->send();
                        return;
                    }
                    
                    return response()->download(
                        Storage::path($this->record->file_path),
                        $this->record->name . '.' . $this->record->file_type
                    );
                }),

            Actions\Action::make('preview')
                ->label('Preview')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->visible(fn () => $this->record->status === 'completed' && $this->record->file_type === 'pdf')
                ->url(fn () => '/unit-manage/reports/' . $this->record->id . '/preview')
                ->openUrlInNewTab(),

            Actions\EditAction::make()
                ->visible(fn () => $this->record->status === 'pending' || $this->record->status === 'failed'),

            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->status === 'pending' || $this->record->status === 'failed'),
        ];
    }
}