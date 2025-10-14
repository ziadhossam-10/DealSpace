<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Illuminate\Support\Collection;

class PeopleExportTemplate implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithTitle, WithMapping, WithColumnFormatting
{
    use Exportable;

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // Return collection with one sample row
        return new Collection([
            [
                'name' => 'William Riker',
                'first_name' => 'William',
                'last_name' => 'Riker',
                'prequalified' => false,
                'stage' => 'New Lead',
                'source' => 'Website',
                'source_url' => 'https://example.com/contact',
                'contacted' => 0,
                'price' => 310000,
                'assigned_lender_id' => '',
                'assigned_lender_name' => '',
                'assigned_user_id' => '',
                'assigned_to' => 'Gerald Leenerts',
                'timeframe_id' => 1,
                'email' => 'william.riker@example.com',
                'email_type' => 'work',
                'email_status' => 'Not Validated',
                'phone' => '+1234567890',
                'phone_type' => 'mobile',
                'phone_status' => 'Not Validated',
                'collaborator_name' => 'Gerald Leenerts',
                'collaborator_assigned' => true,
                'collaborator_role' => 'Broker',
                'street_address' => '123 Enterprise Avenue',
                'city' => 'San Francisco',
                'state' => 'CA',
                'postal_code' => '94105',
                'country' => 'USA',
                'address_type' => 'home',
                'tag_name' => 'VIP Client',
                'tag_color' => '#FF0000',
                'tag_description' => 'High-priority client with urgent needs'
            ]
        ]);
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'name', 'first_name', 'last_name', 'prequalified', 'stage',
            'source', 'source_url', 'contacted', 'price',
            'assigned_lender_id', 'assigned_lender_name', 'assigned_user_id',
            'assigned_to', 'timeframe_id',

            // Email fields
            'email', 'email_type', 'email_status',

            // Phone fields
            'phone', 'phone_type', 'phone_status',

            // Collaborator fields
            'collaborator_name', 'collaborator_assigned', 'collaborator_role',

            // Address fields
            'street_address', 'city', 'state', 'postal_code', 'country', 'address_type',

            // Tag fields
            'tag_name', 'tag_color', 'tag_description'
        ];
    }

    /**
     * @param mixed $row
     * @return array
     */
    public function map($row): array
    {
        return [
            $row['name'],
            $row['first_name'],
            $row['last_name'],
            $row['prequalified'] ? 'TRUE' : 'FALSE',
            $row['stage'],
            $row['source'],
            $row['source_url'],
            $row['contacted'],
            $row['price'],
            $row['assigned_lender_id'],
            $row['assigned_lender_name'],
            $row['assigned_user_id'],
            $row['assigned_to'],
            $row['timeframe_id'],
            $row['email'],
            $row['email_type'],
            $row['email_status'],
            $row['phone'],
            $row['phone_type'],
            $row['phone_status'],
            $row['collaborator_name'],
            $row['collaborator_assigned'] ? 'TRUE' : 'FALSE',
            $row['collaborator_role'],
            $row['street_address'],
            $row['city'],
            $row['state'],
            $row['postal_code'],
            $row['country'],
            $row['address_type'],
            $row['tag_name'],
            $row['tag_color'],
            $row['tag_description']
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'People Import Template';
    }

    /**
     * @return array
     */
    public function columnFormats(): array
    {
        return [
            'H' => NumberFormat::FORMAT_NUMBER, // contacted
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // price
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        // Add some extra styling for the template
        $lastColumn = $sheet->getHighestColumn();

        // Add comments to important columns
        $sheet->getComment('A1')->getText()->createTextRun('Full name of the person');
        $sheet->getComment('D1')->getText()->createTextRun('TRUE or FALSE if person is prequalified');
        $sheet->getComment('O1')->getText()->createTextRun('Valid email address');
        $sheet->getComment('R1')->getText()->createTextRun('Phone number with country code');

        return [
            // Style the first row as bold text with a gray background
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E0E0E0']
                ]
            ],
            // Style the sample data row
            2 => [
                'font' => ['italic' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F5F5F5']
                ]
            ],
            // Add a border to all cells
            'A1:' . $lastColumn . '2' => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC']
                    ]
                ]
            ]
        ];
    }
}