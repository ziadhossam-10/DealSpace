<?php

namespace App\Repositories\EmailTemplates;

use App\Models\EmailTemplate;

interface EmailTemplatesRepositoryInterface
{
    public function getAll(int $userId, int $perPage = 15, int $page = 1, string $search = null);
    public function findById(int $emailTemplateId): ?EmailTemplate;
    public function create(array $data): EmailTemplate;
    public function update(EmailTemplate $emailTemplate, array $data): EmailTemplate;
    public function delete(EmailTemplate $emailTemplate): bool;
    public function getByUserId(int $userId, int $perPage = 15, int $page = 1);
    public function getSharedTemplates(int $perPage = 15, int $page = 1);
    public function deleteAll(): int;
    public function deleteAllExcept(array $ids): int;
    public function deleteSome(array $ids): int;
}
