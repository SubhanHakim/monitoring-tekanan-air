<?php

namespace App\Http\Controllers\Unit;

use App\Http\Controllers\Controller;
use App\Models\UnitReport;
use App\Models\Device;
use App\Services\UnitReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UnitReportController extends Controller
{
    protected $unitReportService;

    public function __construct(UnitReportService $unitReportService)
    {
        $this->unitReportService = $unitReportService;
    }

    public function index()
    {
        $user = Auth::user();
        $unit = $user->unit;
        
        if (!$unit) {
            return redirect()->route('login')->with('error', 'Unit tidak ditemukan');
        }

        if (!$unit->isActive()) {
            return redirect()->route('unit.manage.dashboard')->with('error', 'Unit tidak aktif');
        }

        $reports = UnitReport::where('unit_id', $unit->id)
            ->with(['device', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('unit.management.reports.index', compact('unit', 'reports'));
    }

    public function create()
    {
        $user = Auth::user();
        $unit = $user->unit;
        
        if (!$unit) {
            return redirect()->route('login')->with('error', 'Unit tidak ditemukan');
        }

        if (!$unit->isActive()) {
            return redirect()->route('unit.manage.dashboard')->with('error', 'Unit tidak aktif');
        }

        $devices = Device::where('unit_id', $unit->id)->get();
        
        return view('unit.management.reports.create', compact('unit', 'devices'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $unit = $user->unit;
        
        if (!$unit) {
            return redirect()->route('login')->with('error', 'Unit tidak ditemukan');
        }

        if (!$unit->isActive()) {
            return back()->with('error', 'Unit tidak aktif, tidak dapat membuat laporan');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'report_format' => 'required|in:summary,detailed,statistical',
            'data_source' => 'required|in:all,device,group',
            'device_id' => 'nullable|exists:devices,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'metrics' => 'nullable|array',
            'file_type' => 'required|in:pdf,csv,excel',
        ]);

        if ($validated['device_id']) {
            $device = Device::where('id', $validated['device_id'])
                ->where('unit_id', $unit->id)
                ->first();
            
            if (!$device) {
                return back()->withErrors(['device_id' => 'Perangkat tidak ditemukan']);
            }
        }

        $validated['unit_id'] = $unit->id;
        $validated['created_by'] = $user->id;

        $report = UnitReport::create($validated);

        return redirect()->route('unit.manage.reports.index')
            ->with('success', 'Laporan berhasil dibuat');
    }

    public function generate(UnitReport $report)
    {
        $user = Auth::user();
        
        if ($report->unit_id !== $user->unit_id) {
            return back()->with('error', 'Unauthorized access');
        }

        if (!$user->unit->isActive()) {
            return back()->with('error', 'Unit tidak aktif, tidak dapat generate laporan');
        }

        try {
            Log::info('Starting unit report generation', [
                'unit_id' => $report->unit_id,
                'report_id' => $report->id,
                'user_id' => $user->id,
            ]);

            $report->update(['status' => 'processing']);

            $reportData = $this->unitReportService->generateReport($report);
            $filePath = $this->unitReportService->generatePDF($report, $reportData);

            $report->update([
                'file_path' => $filePath,
                'status' => 'completed',
                'generated_at' => now(),
            ]);

            Log::info('Unit report generated successfully', [
                'unit_id' => $report->unit_id,
                'report_id' => $report->id,
                'file_path' => $filePath,
            ]);

            return redirect()->route('unit.manage.reports.index')
                ->with('success', 'Laporan berhasil dibuat');

        } catch (\Exception $e) {
            Log::error('Unit report generation failed', [
                'unit_id' => $report->unit_id,
                'report_id' => $report->id,
                'error' => $e->getMessage(),
            ]);

            $report->update(['status' => 'failed']);

            return redirect()->route('unit.manage.reports.index')
                ->with('error', 'Gagal membuat laporan: ' . $e->getMessage());
        }
    }

    public function download(UnitReport $report)
    {
        $user = Auth::user();
        
        if ($report->unit_id !== $user->unit_id) {
            return back()->with('error', 'Unauthorized access');
        }

        if (!$user->unit->isActive()) {
            return back()->with('error', 'Unit tidak aktif');
        }

        if (!$report->file_path || !file_exists(storage_path('app/' . $report->file_path))) {
            return back()->with('error', 'File laporan tidak ditemukan');
        }

        return response()->download(
            storage_path('app/' . $report->file_path),
            basename($report->file_path)
        );
    }

    public function preview(UnitReport $report)
    {
        $user = Auth::user();
        
        if ($report->unit_id !== $user->unit_id) {
            return back()->with('error', 'Unauthorized access');
        }

        if (!$user->unit->isActive()) {
            return back()->with('error', 'Unit tidak aktif');
        }

        if (!$report->file_path || !file_exists(storage_path('app/' . $report->file_path))) {
            return back()->with('error', 'File laporan tidak ditemukan');
        }

        return response()->file(storage_path('app/' . $report->file_path));
    }
}