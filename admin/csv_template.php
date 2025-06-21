<?php
require_once '../config.php';
require_once '../includes/auth.php';

require_admin();

// Set headers to download as CSV
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="project_import_template.csv"');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');

// Create output stream
$output = fopen('php://output', 'w');

// CSV headers matching the upload process
$headers = [
    'project_name',
    'description', 
    'department',
    'county',
    'sub_county',
    'ward',
    'location_address',
    'location_coordinates',
    'project_year',
    'start_date',
    'expected_completion_date',
    'contractor_name',
    'contractor_contact',
    'step_name',
    'step_description'
];

// Write headers
fputcsv($output, $headers);

// Add sample data row
$sample_data = [
    'Migori County Health Center Construction',
    'Construction of a modern health center to serve the local community with medical facilities and equipment',
    'Health',
    'Migori',
    'Migori Central',
    'Central Ward',
    'Migori Town Center, near the main market',
    '-1.0634,34.4731',
    '2024',
    '2024-01-15',
    '2024-12-31',
    'ABC Construction Ltd',
    '+254712345678',
    'Project Planning & Approval',
    'Initial project planning, design review, and regulatory approval process'
];

fputcsv($output, $sample_data);

// Sample data rows
$sample_data = [
    [
        'Migori-Isebania Road Improvement',
        'Upgrading of 15km stretch of Migori-Isebania road with tarmac surface and proper drainage',
        'Roads and Transport',
        'Migori',
        'Migori',
        'Central Sakwa',
        'Migori-Isebania Highway, Migori Town',
        '-1.0634,34.4731',
        '2024',
        '2024-01-15',
        '2024-12-31',
        'Kens Construction Ltd',
        '+254712345678',
        'Road Survey and Design',
        'Conduct topographical survey and prepare detailed engineering designs'
    ],
    [
        'Rongo Market Upgrade',
        'Construction of modern market stalls with proper sanitation and drainage facilities',
        'Trade and Commerce',
        'Migori',
        'Rongo',
        'East Kamagambo',
        'Rongo Town Center',
        '-1.2345,34.6789',
        '2024',
        '2024-03-01',
        '2024-08-30',
        'Unity Builders',
        '+254723456789',
        'Site Preparation',
        'Clear site and prepare foundation for market construction'
    ],
    [
        'Nyatike Health Center Extension',
        'Addition of maternity wing and medical equipment procurement',
        'Health Services',
        'Migori',
        'Nyatike',
        'North Kadem',
        'Nyatike Health Center',
        '-1.1234,34.1234',
        '2024',
        '2024-02-01',
        '2024-11-30',
        'Medical Contractors Kenya',
        '+254734567890',
        'Architectural Planning',
        'Design maternity wing and plan equipment installation'
    ]
];

// Write sample data
foreach ($sample_data as $row) {
    fputcsv($output, $row);
}

fputcsv($output, $sample_data);

// Add more sample data rows
$sample_data_2 = [
    'Migori Road Improvement Project',
    'Upgrading and tarmacking of rural access roads',
    'Roads and Transport',
    'Migori',
    'Migori Central',
    'God Jope Ward',
    'Migori-Isebania Highway, Migori Town',
    '-1.0634,34.4731',
    '2024',
    '2024-01-15',
    '2024-12-31',
    'XYZ Roads Ltd',
    '+254722345679',
    'Site Survey & Planning',
    'Conduct detailed site survey and develop construction plans'
];

$sample_data_3 = [
    'Rongo Education Infrastructure',
    'Construction of classroom blocks and learning facilities',
    'Education',
    'Migori',
    'Rongo',
    'East Kamagambo',
    'Rongo Town Center',
    '-1.2345,34.6789',
    '2024',
    '2024-03-01',
    '2024-08-30',
    'Education Builders',
    '+254733456780',
    'Foundation Work',
    'Excavation and foundation construction'
];

$sample_data_4 = [
    'Nyatike Water Supply Project',
    'Installation of boreholes and water distribution system',
    'Water and Sanitation',
    'Migori',
    'Nyatike',
    'North Kadem',
    'Nyatike Health Center',
    '-1.1234,34.1234',
    '2024',
    '2024-02-01',
    '2024-11-30',
    'Water Works Ltd',
    '+254744567891',
    'Borehole Drilling',
    'Drilling and testing of water boreholes'
];

fputcsv($output, $sample_data_2);
fputcsv($output, $sample_data_3);
fputcsv($output, $sample_data_4);

fclose($output);
?>