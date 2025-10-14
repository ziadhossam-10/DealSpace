<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'body' => $this->body,
            'body_html' => $this->body_html,
            'from_email' => $this->from_email,
            'from_name' => $this->from_name,
            'to_email' => $this->to_email,
            'to_name' => $this->to_name,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
            'reply_to' => $this->reply_to,
            'message_id' => $this->message_id,
            'thread_id' => $this->thread_id,
            'is_incoming' => $this->is_incoming,
            'is_read' => $this->is_read,
            'is_starred' => $this->is_starred,
            'is_archived' => $this->is_archived,
            'is_deleted' => $this->is_deleted,
            'is_spam' => $this->is_spam,
            'is_processed' => $this->is_processed,
            'headers' => $this->headers,
            'attachments' => $this->attachments,
            'sent_at' => $this->sent_at,
            'received_at' => $this->received_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships
            'person' => new PersonResource($this->whenLoaded('person')),
            'user' => new UserResource($this->whenLoaded('user')),
            'email_account' => new EmailAccountResource($this->whenLoaded('emailAccount')),
        ];
    }
}
