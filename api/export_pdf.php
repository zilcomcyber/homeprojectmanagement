<?php
require_once '../config.php';
require_once '../includes/functions.php';

// Simple PDF generation without external libraries
// For production, consider using TCPDF or similar library

try {
    // Get filter parameters
    $filters = [
        'search' => $_GET['search'] ?? '',
        'status' => $_GET['status'] ?? '',
        'department' => $_GET['department'] ?? '',
        'ward' => $_GET['ward'] ?? '',
        'sub_county' => $_GET['sub_county'] ?? '',
        'year' => $_GET['year'] ?? '',
        'min_budget' => $_GET['min_budget'] ?? '',
        'max_budget' => $_GET['max_budget'] ?? ''
    ];

    // Remove empty filters
    $filters = array_filter($filters, function($value) {
        return $value !== '' && $value !== null;
    });

    // Check if single project export
    if (isset($_GET['project_id'])) {
        $project = get_project_by_id($_GET['project_id']);
        if (!$project) {
            http_response_code(404);
            echo "Project not found";
            exit;
        }
        $projects = [$project];
        $filename = "project_{$project['id']}_details.pdf";
    } else {
        // Get projects based on filters
        $projects = get_projects($filters);
        $filename = "county_projects_" . date('Y-m-d_H-i-s') . ".pdf";
    }

    // Generate HTML content for PDF
    $html = generatePDFHTML($projects, $filters);

    // Set headers for PDF download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    // Simple HTML to PDF conversion using browser's print functionality
    // In production, use a proper PDF library like TCPDF or mPDF
    echo $html;

} catch (Exception $e) {
    error_log("PDF Export Error: " . $e->getMessage());
    http_response_code(500);
    echo "Export failed: " . $e->getMessage();
}

function generatePDFHTML($projects, $filters) {
    $total_projects = count($projects);
    $total_budget = array_sum(array_column($projects, 'budget'));
    
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>County Projects Report</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                margin: 20px; 
                line-height: 1.4; 
                color: #333;
            }
            .header { 
                text-align: center; 
                margin-bottom: 30px; 
                border-bottom: 2px solid #333;
                padding-bottom: 20px;
            }
            .header h1 { 
                margin: 0; 
                color: #2563eb; 
                font-size: 24px;
            }
            .header .subtitle { 
                color: #666; 
                margin-top: 5px;
                font-size: 14px;
            }
            .summary { 
                background: #f8f9fa; 
                padding: 15px; 
                margin-bottom: 20px; 
                border-radius: 5px;
                border-left: 4px solid #2563eb;
            }
            .summary h3 { 
                margin: 0 0 10px 0; 
                color: #2563eb;
            }
            .summary-grid { 
                display: grid; 
                grid-template-columns: repeat(2, 1fr); 
                gap: 15px;
            }
            .summary-item { 
                background: white; 
                padding: 10px; 
                border-radius: 3px;
                border: 1px solid #e5e7eb;
            }
            .summary-item strong { 
                display: block; 
                color: #374151; 
                font-size: 18px;
            }
            .summary-item span { 
                color: #6b7280; 
                font-size: 12px;
            }
            .filters { 
                background: #f1f5f9; 
                padding: 10px; 
                margin-bottom: 20px; 
                border-radius: 5px;
                border: 1px solid #cbd5e1;
            }
            .filters h4 { 
                margin: 0 0 10px 0; 
                font-size: 14px;
                color: #475569;
            }
            .project { 
                border: 1px solid #e5e7eb; 
                margin-bottom: 20px; 
                padding: 15px; 
                border-radius: 5px;
                page-break-inside: avoid;
            }
            .project-header { 
                border-bottom: 1px solid #e5e7eb; 
                padding-bottom: 10px; 
                margin-bottom: 15px;
            }
            .project-title { 
                font-size: 18px; 
                font-weight: bold; 
                color: #1f2937; 
                margin: 0 0 5px 0;
            }
            .project-status { 
                display: inline-block; 
                padding: 3px 8px; 
                border-radius: 12px; 
                font-size: 11px; 
                font-weight: bold; 
                text-transform: uppercase;
            }
            .status-planning { background: #fef3c7; color: #92400e; }
            .status-ongoing { background: #dbeafe; color: #1e40af; }
            .status-completed { background: #d1fae5; color: #065f46; }
            .status-suspended { background: #fed7aa; color: #c2410c; }
            .status-cancelled { background: #fecaca; color: #991b1b; }
            .project-grid { 
                display: grid; 
                grid-template-columns: repeat(2, 1fr); 
                gap: 15px; 
                margin-top: 15px;
            }
            .project-info { 
                background: #f9fafb; 
                padding: 10px; 
                border-radius: 3px;
            }
            .project-info dt { 
                font-weight: bold; 
                color: #374151; 
                font-size: 12px; 
                margin-bottom: 2px;
            }
            .project-info dd { 
                margin: 0 0 8px 0; 
                color: #6b7280; 
                font-size: 14px;
            }
            .progress-bar { 
                width: 100%; 
                height: 8px; 
                background: #e5e7eb; 
                border-radius: 4px; 
                overflow: hidden;
                margin-top: 5px;
            }
            .progress-fill { 
                height: 100%; 
                background: #10b981; 
                border-radius: 4px;
            }
            .footer { 
                margin-top: 30px; 
                text-align: center; 
                font-size: 12px; 
                color: #6b7280; 
                border-top: 1px solid #e5e7eb; 
                padding-top: 15px;
            }
            @media print {
                .project { page-break-inside: avoid; }
                body { margin: 0; }
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>' . htmlspecialchars(APP_NAME) . '</h1>
            <div class="subtitle">County Projects Report - Generated on ' . date('F j, Y \a\t g:i A') . '</div>
        </div>';

    // Add filters if any
    $active_filters = array_filter($filters);
    if (!empty($active_filters)) {
        $html .= '<div class="filters">
            <h4>Applied Filters:</h4>';
        foreach ($active_filters as $key => $value) {
            $html .= '<strong>' . ucfirst(str_replace('_', ' ', $key)) . ':</strong> ' . htmlspecialchars($value) . ' &nbsp; ';
        }
        $html .= '</div>';
    }

    // Summary section
    $html .= '<div class="summary">
        <h3>Report Summary</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <strong>' . number_format($total_projects) . '</strong>
                <span>Total Projects</span>
            </div>
            <div class="summary-item">
                <strong>' . format_currency($total_budget) . '</strong>
                <span>Total Budget</span>
            </div>
        </div>
    </div>';

    // Projects section
    if (empty($projects)) {
        $html .= '<div class="project">
            <h3 style="text-align: center; color: #6b7280;">No projects found matching the criteria.</h3>
        </div>';
    } else {
        foreach ($projects as $project) {
            $status_class = 'status-' . $project['status'];
            $progress_width = max(0, min(100, intval($project['progress_percentage'])));
            
            $html .= '<div class="project">
                <div class="project-header">
                    <div class="project-title">' . htmlspecialchars($project['project_name']) . '</div>
                    <span class="project-status ' . $status_class . '">' . ucfirst($project['status']) . '</span>
                </div>';

            if (!empty($project['description'])) {
                $html .= '<p style="margin: 0 0 15px 0; color: #4b5563;">' . htmlspecialchars($project['description']) . '</p>';
            }

            $html .= '<div class="project-grid">
                <div class="project-info">
                    <dt>Department</dt>
                    <dd>' . htmlspecialchars($project['department_name']) . '</dd>
                    
                    <dt>Location</dt>
                    <dd>' . htmlspecialchars($project['ward_name']) . ', ' . htmlspecialchars($project['sub_county_name']) . '</dd>
                    
                    <dt>Year</dt>
                    <dd>' . htmlspecialchars($project['project_year']) . '</dd>
                </div>
                
                <div class="project-info">
                    <dt>Budget</dt>
                    <dd>' . format_currency($project['budget']) . '</dd>';

            if (!empty($project['contractor_name'])) {
                $html .= '<dt>Contractor</dt>
                    <dd>' . htmlspecialchars($project['contractor_name']) . '</dd>';
            }

            if (!empty($project['start_date'])) {
                $html .= '<dt>Start Date</dt>
                    <dd>' . format_date($project['start_date']) . '</dd>';
            }

            $html .= '</div>
            </div>';

            if ($project['progress_percentage'] > 0) {
                $html .= '<div style="margin-top: 15px;">
                    <dt style="font-weight: bold; color: #374151; font-size: 12px; margin-bottom: 5px;">Progress: ' . $project['progress_percentage'] . '%</dt>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ' . $progress_width . '%"></div>
                    </div>
                </div>';
            }

            $html .= '</div>';
        }
    }

    $html .= '<div class="footer">
        <p>This report was generated automatically by ' . htmlspecialchars(APP_NAME) . '</p>
        <p>For questions or concerns, please contact the county administration.</p>
    </div>

    </body>
    </html>';

    return $html;
}
?>
