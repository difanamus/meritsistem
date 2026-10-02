<?php

namespace App\Services;

interface PersonnelSource
{
    /** @return array{records: list<array<string, mixed>>, next_page: ?int} */
    public function fetch(int $version, int $afterVersion, int $page, int $limit): array;
}
