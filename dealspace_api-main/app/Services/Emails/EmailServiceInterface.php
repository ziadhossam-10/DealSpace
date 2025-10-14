<?php

namespace App\Services\Emails;

use App\Models\Email;
use App\Models\EmailAccount;

interface EmailServiceInterface
{
    /**
     * Get all emails for a person with pagination.
     *
     * @param int $perPage Number of emails per page (default: 15)
     * @param int $page Current page number (default: 1)
     * @param int $personId The ID of the person whose emails to retrieve
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Paginated collection of emails
     */
    public function getAll(int $personId, int $perPage = 15, int $page = 1);
    /**
     * Get a specific email by ID.
     *
     * @param int $id The ID of the email to retrieve
     * @return Email The Email model instance
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When email is not found
     */
    public function findById(int $id): Email;

    /**
     * Create a new email record.
     *
     * @param array $data The email data including:
     *                    - 'person_id' (int) The ID of the person associated with the email
     *                    - 'subject' (string) The subject line of the email
     *                    - 'body' (string) The body content of the email
     *                    - 'to_email' (string) The email address the email was sent to
     *                    - 'from_email' (string) The email address the email was sent from
     *                    - 'is_incoming' (bool) Whether the email was incoming from a person
     *                    - 'external_label' (string, optional) Descriptive text for the timeline
     *                    - 'external_url' (string, optional) Link for the timeline
     * @return Email The newly created Email model instance
     */
    public function create(array $data): Email;

    /**
     * Send an email using the specified email account and create a record of it.
     *
     * @param array $data The email data including:
     *                         - 'person_id' (int) The ID of the person associated with the email
     *                         - 'subject' (string) The subject line of the email
     *                         - 'body' (string) The plain text body content
     *                         - 'body_html' (string, optional) The HTML body content
     *                         - 'to_email' (string) The recipient email address
     *                          - 'account_id' (int) The sender email account ID
     * @return Email The created Email model instance with send status
     * @throws \Exception When email sending fails or access token is invalid
     */
    public function sendEmail(array $data, int $userId): Email;

    public function fetchNewEmails(EmailAccount $account): array;
}
