<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Report;
use App\Models\Device;
use App\Models\DeviceGroup;
use App\Models\User;
use Carbon\Carbon;

class ReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan ada user admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator',
                'password' => bcrypt('password'),
            ]
        );

        // Buat beberapa contoh laporan
        // 1. Laporan Harian
        Report::create([
            'name' => 'Laporan Harian Tekanan Air',
            'type' => 'daily',
            'description' => 'Laporan monitoring tekanan air harian untuk semua perangkat',
            'start_date' => Carbon::yesterday(),
            'end_date' => Carbon::yesterday(),
            'device_id' => null, // Semua perangkat
            'device_group_id' => null, // Semua grup
            'is_scheduled' => true,
            'schedule_frequency' => 'daily',
            'recipients' => '["admin@example.com"]', // Gunakan recipients, bukan email_on_completion
            'parameters' => [
                'include_anomalies' => true,
                'include_charts' => true,
                'anomaly_threshold' => '2.0',
                'chart_type' => 'line'
            ],
            'created_by' => $admin->id
        ]);

        // 2. Laporan Mingguan
        Report::create([
            'name' => 'Laporan Mingguan Tekanan Air',
            'type' => 'weekly',
            'description' => 'Laporan monitoring tekanan air mingguan untuk semua perangkat',
            'start_date' => Carbon::now()->startOfWeek()->subWeek(),
            'end_date' => Carbon::now()->endOfWeek()->subWeek(),
            'device_id' => null, // Semua perangkat
            'device_group_id' => null, // Semua grup
            'is_scheduled' => true,
            'schedule_frequency' => 'weekly',
            'recipients' => '["manager@example.com","teknisi@example.com"]', // Gunakan recipients sebagai JSON string
            'parameters' => [
                'include_anomalies' => true,
                'include_charts' => true,
                'anomaly_threshold' => '2.5',
                'chart_type' => 'line',
                'analyze_uptime' => true
            ],
            'created_by' => $admin->id
        ]);

        // 3. Laporan Bulanan
        Report::create([
            'name' => 'Laporan Bulanan Tekanan Air',
            'type' => 'monthly',
            'description' => 'Laporan monitoring tekanan air bulanan untuk semua perangkat',
            'start_date' => Carbon::now()->startOfMonth()->subMonth(),
            'end_date' => Carbon::now()->endOfMonth()->subMonth(),
            'device_id' => null, // Semua perangkat
            'device_group_id' => null, // Semua grup
            'is_scheduled' => true,
            'schedule_frequency' => 'monthly',
            'recipients' => '["direktur@example.com"]', // Gunakan recipients sebagai JSON string
            'parameters' => [
                'include_anomalies' => true,
                'include_charts' => true,
                'anomaly_threshold' => '3.0',
                'chart_type' => 'bar',
                'analyze_uptime' => true
            ],
            'created_by' => $admin->id
        ]);

        // Jika sudah ada devices, buat laporan per device juga
        $device = Device::first();
        if ($device) {
            Report::create([
                'name' => 'Laporan Tekanan Air - ' . $device->name,
                'type' => 'custom',
                'description' => 'Laporan monitoring tekanan air khusus untuk perangkat ' . $device->name,
                'start_date' => Carbon::now()->subDays(14),
                'end_date' => Carbon::now(),
                'device_id' => $device->id,
                'device_group_id' => null,
                'is_scheduled' => false,
                'schedule_frequency' => null,
                'recipients' => '[]',
                'parameters' => [
                    'include_anomalies' => true,
                    'include_charts' => true,
                    'anomaly_threshold' => '2.0',
                    'chart_type' => 'line'
                ],
                'created_by' => $admin->id
            ]);
        }

        // Jika sudah ada device groups, buat laporan per group juga
        $deviceGroup = DeviceGroup::first();
        if ($deviceGroup) {
            Report::create([
                'name' => 'Laporan Grup - ' . $deviceGroup->name,
                'type' => 'custom',
                'description' => 'Laporan monitoring tekanan air untuk grup ' . $deviceGroup->name,
                'start_date' => Carbon::now()->subMonth(),
                'end_date' => Carbon::now(),
                'device_id' => null,
                'device_group_id' => $deviceGroup->id,
                'is_scheduled' => false,
                'schedule_frequency' => null,
                'recipients' => '[]', // Array kosong sebagai JSON string
                'parameters' => [
                    'include_anomalies' => true,
                    'include_charts' => true,
                    'anomaly_threshold' => '2.0',
                    'chart_type' => 'area'
                ],
                'created_by' => $admin->id
            ]);
        }
    }
}