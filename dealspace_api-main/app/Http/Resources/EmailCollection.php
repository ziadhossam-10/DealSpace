<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class EmailCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'items' => $this->collection->map(function ($email) {
                return [
                    'id' => $email->id,
                    'subject' => $email->subject,
                    'from_email' => $email->from_email,
                    'from_name' => $email->from_name,
                    'to_email' => $email->to_email,
                    'to_name' => $email->to_name,
                    'body' => $email->body,
                    'body_html' => $email->body_html,
                    'is_incoming' => $email->is_incoming,
                    'is_read' => $email->is_read,
                    'is_starred' => $email->is_starred,
                    'is_archived' => $email->is_archived,
                    'is_deleted' => $email->is_deleted,
                    'is_spam' => $email->is_spam,
                    'sent_at' => $email->sent_at,
                    'received_at' => $email->received_at,
                    'created_at' => $email->created_at,
                    // You can include relationships here if needed
                    'person_id' => $email->person_id,
                    'user_id' => $email->user_id,
                    'email_account_id' => $email->email_account_id,
                ];
            }),
            'meta' => [
                'current_page' => $this->resource->currentPage(),
                'per_page' => $this->resource->perPage(),
                'total' => $this->resource->total(),
                'last_page' => $this->resource->lastPage(),
            ]
        ];
    }
}
