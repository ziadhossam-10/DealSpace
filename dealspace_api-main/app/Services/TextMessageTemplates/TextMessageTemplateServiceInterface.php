<?php

namespace App\Services\TextMessageTemplates;

use App\Models\TextMessageTemplate;

interface TextMessageTemplateServiceInterface
{
    public function getAll(int $userId, int $perPage = 15, int $page = 1, string $search = null);
    public function findById(int $textMessageTemplateId): TextMessageTemplate;
    public function create(array $data): TextMessageTemplate;
    public function update(int $textMessageTemplateId, array $data): TextMessageTemplate;
    public function delete(int $textMessageTemplateId): bool;
    public function bulkDelete(array $params): int;
}