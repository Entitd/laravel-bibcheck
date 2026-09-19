<?php

namespace App\DTO;

readonly class BibEntryDTO
{
    public function __construct(
        public string $type,
        public string $key,
        public array $fields, // ['title' => '...', 'year' => '...']
        public int $startLine
    ) {}

    public function getField(string $name): ?string
    {
        return $this->fields[strtolower($name)] ?? null;
    }
}
