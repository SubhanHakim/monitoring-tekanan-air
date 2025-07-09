<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function generate(Report $report)
    {
        try {
            // Log untuk debugging
            Log::info('Generating report', [
                'report_id' => $report->id,
                'report_format' => $report->report_format,
                'start_date' => $report->start_date,
                'end_date' => $report->end_date,
            ]);

            // Generate report data
            $reportData = $this->reportService->generateReport($report);

            // Log data yang dihasilkan
            Log::info('Report data generated', [
                'report_id' => $report->id,
                'data_count' => count($reportData['data'] ?? []),
                'devices_count' => count($reportData['devices'] ?? []),
            ]);

            // Generate PDF
            $pdfPath = $this->reportService->generatePDF($report, $reportData);
            Log::info('PDF generated', ['path' => $pdfPath]);

            // Generate CSV
            $csvPath = $this->reportService->generateCSV($report, $reportData);
            Log::info('CSV generated', ['path' => $csvPath]);

            // Update report record
            $report->update([
                'last_generated_at' => now(),
                'last_generated_file' => $pdfPath,
                'data' => $reportData,
            ]);

            // Return response dengan link download
            return response()->json([
                'success' => true,
                'message' => 'Laporan berhasil dibuat',
                'data' => [
                    'report_id' => $report->id,
                    'report_name' => $report->name,
                    'pdf_url' => route('reports.download', ['report' => $report->id, 'format' => 'pdf']),
                    'csv_url' => route('reports.download', ['report' => $report->id, 'format' => 'csv']),
                    'preview_url' => route('reports.preview', $report->id),
                    'generated_at' => now()->format('d/m/Y H:i:s'),
                    'pdf_path' => $pdfPath,
                    'csv_path' => $csvPath,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Report generation failed', [
                'report_id' => $report->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat laporan: ' . $e->getMessage(),
                'error_details' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            ], 500);
        }
    }

    public function download(Report $report, Request $request)
    {
        try {
            $format = $request->get('format', 'pdf');

            Log::info('Download request', [
                'report_id' => $report->id,
                'format' => $format,
                'last_generated_file' => $report->last_generated_file,
            ]);

            if ($format === 'csv') {
                return $this->downloadCSV($report);
            }

            return $this->downloadPDF($report);
        } catch (\Exception $e) {
            Log::error('Report download failed', [
                'report_id' => $report->id,
                'format' => $format ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Gagal mengunduh laporan: ' . $e->getMessage(),
                'details' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            ], 500);
        }
    }

    private function downloadPDF(Report $report)
    {
        try {
            // Check if PDF file exists
            if (!$report->last_generated_file || !Storage::exists($report->last_generated_file)) {
                Log::info('PDF not found, generating new one', [
                    'report_id' => $report->id,
                    'last_generated_file' => $report->last_generated_file,
                    'storage_exists' => $report->last_generated_file ? Storage::exists($report->last_generated_file) : false,
                ]);

                // Generate PDF baru
                $reportData = $this->reportService->generateReport($report);
                $pdfPath = $this->reportService->generatePDF($report, $reportData);

                $report->update([
                    'last_generated_file' => $pdfPath,
                    'last_generated_at' => now(),
                ]);

                Log::info('New PDF generated', [
                    'report_id' => $report->id,
                    'new_pdf_path' => $pdfPath,
                ]);
            }

            $fullPath = storage_path('app/' . $report->last_generated_file);

            Log::info('PDF download attempt', [
                'report_id' => $report->id,
                'full_path' => $fullPath,
                'file_exists' => File::exists($fullPath),
                'file_size' => File::exists($fullPath) ? File::size($fullPath) : 0,
                'storage_path' => storage_path('app/'),
                'relative_path' => $report->last_generated_file,
            ]);

            if (!File::exists($fullPath)) {
                // Coba generate ulang sekali lagi
                Log::warning('PDF still not found, trying to regenerate', [
                    'report_id' => $report->id,
                    'full_path' => $fullPath,
                ]);

                $reportData = $this->reportService->generateReport($report);
                $pdfPath = $this->reportService->generatePDF($report, $reportData);

                $report->update([
                    'last_generated_file' => $pdfPath,
                    'last_generated_at' => now(),
                ]);

                $fullPath = storage_path('app/' . $pdfPath);

                if (!File::exists($fullPath)) {
                    throw new \Exception('File PDF tidak dapat dibuat: ' . $fullPath);
                }
            }

            $fileName = 'laporan_' . $report->id . '_' . now()->format('Y-m-d') . '.pdf';

            return Response::download($fullPath, $fileName, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ]);
        } catch (\Exception $e) {
            Log::error('PDF download failed', [
                'report_id' => $report->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    private function downloadCSV(Report $report)
    {
        $csvPath = str_replace('.pdf', '.csv', $report->last_generated_file);

        if (!Storage::exists($csvPath)) {
            Log::info('CSV not found, generating new one', [
                'report_id' => $report->id,
                'csv_path' => $csvPath,
            ]);

            // Generate CSV jika belum ada
            $reportData = $this->reportService->generateReport($report);
            $csvPath = $this->reportService->generateCSV($report, $reportData);
        }

        $fullPath = storage_path('app/' . $csvPath);

        Log::info('CSV download path', [
            'report_id' => $report->id,
            'full_path' => $fullPath,
            'file_exists' => File::exists($fullPath),
        ]);

        if (!File::exists($fullPath)) {
            throw new \Exception('File CSV tidak ditemukan: ' . $fullPath);
        }

        $fileName = 'laporan_' . $report->id . '_' . now()->format('Y-m-d') . '.csv';

        return Response::download($fullPath, $fileName, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function preview(Report $report)
    {
        try {
            Log::info('Preview request', ['report_id' => $report->id]);

            $reportData = $this->reportService->generateReport($report);

            $viewName = match ($report->report_format) {
                'statistical' => 'reports.statistical',
                'detailed' => 'reports.detailed',
                default => 'reports.summary',
            };

            Log::info('Using view', [
                'report_id' => $report->id,
                'view' => $viewName,
                'data_keys' => array_keys($reportData),
            ]);

            return view($viewName, $reportData);
        } catch (\Exception $e) {
            Log::error('Report preview failed', [
                'report_id' => $report->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Gagal menampilkan preview: ' . $e->getMessage(),
                'details' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            ], 500);
        }
    }
}
