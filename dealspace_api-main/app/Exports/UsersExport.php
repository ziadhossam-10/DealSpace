<?php

namespace App\Exports;

use App\Enums\RoleEnum;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\Exportable;

class UsersExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use Exportable;

    protected $isAllSelected;
    protected $exceptionIds;
    protected $ids;

    /**
     * Constructor
     *
     * @param array $params Parameters to control the export operation
     *     - is_all_selected (bool): Export all users except those in exception_ids
     *     - exception_ids (array): IDs to exclude from export
     *     - ids (array): IDs of users to export
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
        if ($this->isAllSelected) {
            if (!empty($this->exceptionIds)) {
                return User::whereNotIn('id', $this->exceptionIds)->get();
            } else {
                return User::all();
            }
        } else {
            if (!empty($this->ids)) {
                return User::whereIn('id', $this->ids)->get();
            } else {
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
            'Email',
            'Role',
            'Created At',
            'Updated At'
        ];
    }

    /**
     * @param User $user
     * @return array
     */
    public function map($user): array
    {
        return [
            $user->id,
            $user->name,
            $user->email,
            RoleEnum::label($user->role->value),
            $user->created_at ? $user->created_at->format('Y-m-d H:i:s') : '',
            $user->updated_at ? $user->updated_at->format('Y-m-d H:i:s') : ''
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
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
