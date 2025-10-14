<?php

namespace App\Exports;

use App\Models\Person;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\Exportable;

class PeopleExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use Exportable;

    protected $isAllSelected;
    protected $exceptionIds;
    protected $ids;

    /**
     * Constructor
     *
     * @param array $params Parameters to control the export operation
     *     - is_all_selected (bool): Export all people except those in exception_ids
     *     - exception_ids (array): IDs to exclude from export
     *     - ids (array): IDs of people to export
     */
    public function __construct(array $params = [])
    {
        $this->isAllSelected = $params['is_all_selected'] ?? false;
        $this->exceptionIds = $params['exception_ids'] ?? [];
        $this->ids = $params['ids'] ?? [];
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // Get people to export based on filters
        if ($this->isAllSelected) {
            if (!empty($this->exceptionIds)) {
                // Export all except those in exception_ids
                return Person::whereNotIn('id', $this->exceptionIds)
                    ->with(['emails', 'phones', 'collaborators', 'addresses', 'tags'])
                    ->get();
            } else {
                // Export all
                return Person::with(['emails', 'phones', 'collaborators', 'addresses', 'tags'])
                    ->get();
            }
        } else {
            if (!empty($this->ids)) {
                // Export specific ids
                return Person::whereIn('id', $this->ids)
                    ->with(['emails', 'phones', 'collaborators', 'addresses', 'tags'])
                    ->get();
            } else {
                // No records to export, return empty collection
                return collect([]);
            }
        }
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'First Name',
            'Last Name',
            'Prequalified',
            'Stage',
            'Source',
            'Source URL',
            'Contacted',
            'Price',
            'Assigned Lender ID',
            'Assigned Lender Name',
            'Assigned User ID',
            'Assigned To',
            'Timeframe ID',
            'Created At',
            'Updated At',

            // Primary email
            'Email',
            'Email Type',
            'Email Status',

            // Primary phone
            'Phone',
            'Phone Type',
            'Phone Status',

            // Primary address
            'Street Address',
            'City',
            'State',
            'Postal Code',
            'Country',
            'Address Type',

            // First collaborator
            'Collaborator Name',
            'Collaborator Assigned',
            'Collaborator Role',

            // First tag
            'Tag Name',
            'Tag Color',
            'Tag Description'
        ];
    }

    /**
     * @param Person $person
     * @return array
     */
    public function map($person): array
    {
        $primaryEmail = $person->emailAccounts->firstWhere('is_primary', true) ?? $person->emailAccounts->first();
        $primaryPhone = $person->phones->firstWhere('is_primary', true) ?? $person->phones->first();
        $primaryAddress = $person->addresses->firstWhere('is_primary', true) ?? $person->addresses->first();
        $firstCollaborator = $person->collaborators->first();
        $firstTag = $person->tags->first();

        return [
            $person->id,
            $person->name,
            $person->first_name,
            $person->last_name,
            $person->prequalified ? 'TRUE' : 'FALSE',
            $person->stage ?? 'New Lead',
            $person->source,
            $person->source_url,
            $person->contacted,
            $person->price,
            $person->assigned_lender_id,
            $person->assigned_lender_name,
            $person->assigned_user_id,
            $person->assigned_to,
            $person->timeframe_id,
            $person->created_at ? $person->created_at->format('Y-m-d H:i:s') : '',
            $person->updated_at ? $person->updated_at->format('Y-m-d H:i:s') : '',

            // Primary email
            $primaryEmail ? $primaryEmail->value : '',
            $primaryEmail ? $primaryEmail->type : '',
            $primaryEmail ? $primaryEmail->status : '',

            // Primary phone
            $primaryPhone ? $primaryPhone->value : '',
            $primaryPhone ? $primaryPhone->type : '',
            $primaryPhone ? $primaryPhone->status : '',

            // Primary address
            $primaryAddress ? $primaryAddress->street_address : '',
            $primaryAddress ? $primaryAddress->city : '',
            $primaryAddress ? $primaryAddress->state : '',
            $primaryAddress ? $primaryAddress->postal_code : '',
            $primaryAddress ? $primaryAddress->country : '',
            $primaryAddress ? $primaryAddress->type : '',

            // First collaborator
            $firstCollaborator ? $firstCollaborator->name : '',
            $firstCollaborator ? ($firstCollaborator->assigned ? 'TRUE' : 'FALSE') : '',
            $firstCollaborator ? $firstCollaborator->role : '',

            // First tag
            $firstTag ? $firstTag->name : '',
            $firstTag ? $firstTag->color : '',
            $firstTag ? $firstTag->description : ''
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return void
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text with a gray background
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E0E0E0']
                ]
            ],
        ];
    }
}
