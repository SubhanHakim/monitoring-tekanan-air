<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Report;
use App\Services\ReportService;

class TestReportGeneration extends Command
{
    protected $signature = 'test:report {reportId}';
    protected $description = 'Test report generation';

    public function handle(ReportService $reportService)
    {
        $reportId = $this->argument('reportId');
        $report = Report::find($reportId);
        
        if (!$report) {
            $this->error('Report not found');
            return;
        }

        try {
            $this->info('Generating report...');
            $reportData = $reportService->generateReport($report);
            
            $this->info('Generating PDF...');
            $pdfPath = $reportService->generatePDF($report, $reportData);
            
            $this->info('Generating CSV...');
            $csvPath = $reportService->generateCSV($report, $reportData);
            
            $this->info('Report generated successfully!');
            $this->info('PDF: ' . $pdfPath);
            $this->info('CSV: ' . $csvPath);
            
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
}