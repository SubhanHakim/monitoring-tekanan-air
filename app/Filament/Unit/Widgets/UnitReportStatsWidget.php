<?php

namespace App\Filament\Unit\Widgets;

use App\Models\UnitReport;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class UnitReportStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        
        // ✅ FIX: Jika user tidak ada atau tidak punya unit_id, return empty
        if (!$user || !$user->unit_id) {
            return [
                Stat::make('Total Laporan', 0)
                    ->description('User tidak memiliki unit')
                    ->color('gray'),
            ];
        }
        
        $unitId = $user->unit_id;

        // ✅ FIX: Gunakan try-catch untuk handle error database
        try {
            return [
                Stat::make('Total Laporan', UnitReport::where('unit_id', $unitId)->count())
                    ->description('Laporan yang dibuat')
                    ->descriptionIcon('heroicon-m-document-text')
                    ->color('primary'),

                Stat::make('Pending', UnitReport::where('unit_id', $unitId)->where('status', 'pending')->count())
                    ->description('Menunggu generate')
                    ->descriptionIcon('heroicon-m-clock')
                    ->color('warning'),

                Stat::make('Selesai', UnitReport::where('unit_id', $unitId)->where('status', 'completed')->count())
                    ->description('Siap diunduh')
                    ->descriptionIcon('heroicon-m-check-circle')
                    ->color('success'),

                Stat::make('Bulan Ini', UnitReport::where('unit_id', $unitId)->whereMonth('created_at', now()->month)->count())
                    ->description('Laporan bulan ini')
                    ->descriptionIcon('heroicon-m-calendar')
                    ->color('info'),
            ];
        } catch (\Exception $e) {
            // ✅ FIX: Jika ada error database, return safe stats
            return [
                Stat::make('Error', 'Database Error')
                    ->description('Terjadi kesalahan database')
                    ->color('danger'),
            ];
        }
    }
}