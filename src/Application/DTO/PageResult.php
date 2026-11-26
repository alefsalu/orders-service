<?php

declare(strict_types=1);

namespace App\Application\DTO;

/**
 * @template T
 */
final readonly class PageResult implements \JsonSerializable
{
    /**
     * @param list<T> $content
     */
    public function __construct(
        public array $content,
        public int $page,
        public int $size,
        public int $totalElements,
        public int $totalPages,
    ) {
    }

    /**
     * @return array{content: list<T>, page: int, size: int, totalElements: int, totalPages: int}
     */
    public function jsonSerialize(): array
    {
        return [
            'content' => $this->content,
            'page' => $this->page,
            'size' => $this->size,
            'totalElements' => $this->totalElements,
            'totalPages' => $this->totalPages,
        ];
    }
}
