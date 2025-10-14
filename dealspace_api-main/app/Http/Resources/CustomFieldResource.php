<?php

namespace App\Http\Resources;

use App\Enums\CustomFieldTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomFieldResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'label' => $this->label,
            'type' => $this->type,
            'options' => $this->options,
            'distribution_name' => CustomFieldTypeEnum::label($this->type->value),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
