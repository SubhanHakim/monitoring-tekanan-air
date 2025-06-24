<?php

namespace Database\Seeders;

use App\Models\DeviceGroup;
use Illuminate\Database\Seeder;

class DeviceGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            [
                'name' => 'Wilayah Utara',
                'type' => 'location',
                'description' => 'Perangkat di wilayah utara kota',
                'color' => '#4F46E5',
            ],
            [
                'name' => 'Wilayah Selatan',
                'type' => 'location',
                'description' => 'Perangkat di wilayah selatan kota',
                'color' => '#10B981',
            ],
            [
                'name' => 'Proyek Perbaikan 2023',
                'type' => 'project',
                'description' => 'Perangkat untuk proyek perbaikan tahun 2023',
                'color' => '#F59E0B',
            ],
            [
                'name' => 'Divisi Teknik',
                'type' => 'division',
                'description' => 'Perangkat yang dikelola oleh divisi teknik',
                'color' => '#EF4444',
            ],
        ];

        foreach ($groups as $group) {
            DeviceGroup::create($group);
        }
    }
}