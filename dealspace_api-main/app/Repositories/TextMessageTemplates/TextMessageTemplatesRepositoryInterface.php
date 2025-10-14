<?php

namespace App\Repositories\TextMessageTemplates;

use App\Models\TextMessageTemplate;

interface TextMessageTemplatesRepositoryInterface
{
    public function getAll(int $userId, int $perPage = 15, int $page = 1, string $search = null);
    public function findById(int $textMessageTemplateId): ?TextMessageTemplate;
    public function create(array $data): TextMessageTemplate;
    public function update(TextMessageTemplate $textMessageTemplate, array $data): TextMessageTemplate;
    public function delete(TextMessageTemplate $textMessageTemplate): bool;
    public function getByUserId(int $userId, int $perPage = 15, int $page = 1);
    public function getSharedTemplates(int $perPage = 15, int $page = 1);
    public function deleteAll(): int;
    public function deleteAllExcept(array $ids): int;
    public function deleteSome(array $ids): int;
}
