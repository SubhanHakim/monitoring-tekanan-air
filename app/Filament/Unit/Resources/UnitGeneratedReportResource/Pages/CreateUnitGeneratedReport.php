<?php

namespace App\Filament\Unit\Resources\UnitGeneratedReportResource\Pages;

use App\Filament\Unit\Resources\UnitGeneratedReportResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateUnitGeneratedReport extends CreateRecord
{
    protected static string $resource = UnitGeneratedReportResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['unit_id'] = Auth::user()->unit_id;
        $data['created_by'] = Auth::user()->id;
        $data['status'] = 'pending';

        return $data;
    }
}