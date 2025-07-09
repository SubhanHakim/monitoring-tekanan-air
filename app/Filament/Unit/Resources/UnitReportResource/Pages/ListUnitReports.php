<?php

namespace App\Filament\Unit\Resources\UnitReportResource\Pages;

use App\Filament\Unit\Resources\UnitReportResource;
use App\Filament\Unit\Widgets\UnitReportStatsWidget;  // ✅ FIX NAMESPACE - HAPUS Resources\UnitReportResource\
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListUnitReports extends ListRecords
{
    protected static string $resource = UnitReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Buat Laporan Baru')
                ->icon('heroicon-o-plus')
                ->color('primary'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua Laporan')
                ->badge(fn () => $this->getResource()::getEloquentQuery()->count()),
            
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(fn () => $this->getResource()::getEloquentQuery()->where('status', 'pending')->count())
                ->badgeColor('warning'),
            
            'processing' => Tab::make('Proses')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'processing'))
                ->badge(fn () => $this->getResource()::getEloquentQuery()->where('status', 'processing')->count())
                ->badgeColor('info'),
            
            'completed' => Tab::make('Selesai')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed'))
                ->badge(fn () => $this->getResource()::getEloquentQuery()->where('status', 'completed')->count())
                ->badgeColor('success'),
            
            'failed' => Tab::make('Gagal')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'failed'))
                ->badge(fn () => $this->getResource()::getEloquentQuery()->where('status', 'failed')->count())
                ->badgeColor('danger'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // UnitReportStatsWidget::class,  // ✅ NOW THIS WILL WORK
        ];
    }
}