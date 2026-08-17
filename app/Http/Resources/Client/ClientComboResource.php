<?php

namespace App\Http\Resources\Client;

use Illuminate\Http\Resources\Json\JsonResource;

class ClientComboResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'label' => $this->name . ' (' . ($this->document_number ?? 'Sin documento') . ')',
            'value' => $this->id,
            'meta' => [
                'name' => $this->name,
                'document_type_id' => $this->document_type_id,
                'document_number' => $this->document_number,
                'email' => $this->email,
                'phone' => $this->phone,
            ],
        ];
    }
}
