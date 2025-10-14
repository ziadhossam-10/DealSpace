<?php

namespace App\Services\EmailTemplates;

use App\Models\EmailTemplate;

interface EmailTemplateServiceInterface
{
    public function getAll(int $userId, int $perPage = 15, int $page = 1, string $search = null);
    public function findById(int $emailTemplateId): EmailTemplate;
    public function create(array $data): EmailTemplate;
    public function update(int $emailTemplateId, array $data): EmailTemplate;
    public function delete(int $emailTemplateId): bool;
    public function bulkDelete(array $params): int;
}
