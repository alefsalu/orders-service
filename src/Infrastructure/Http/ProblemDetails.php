<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

final readonly class ProblemDetails implements \JsonSerializable
{
    /**
     * @param list<array{field: string, message: string}>|null $errors
     */
    public function __construct(
        public string $type,
        public string $title,
        public int $status,
        public string $detail,
        public string $instance,
        public string $code,
        public ?array $errors = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $data = [
            'type' => $this->type,
            'title' => $this->title,
            'status' => $this->status,
            'detail' => $this->detail,
            'instance' => $this->instance,
            'code' => $this->code,
        ];

        if ($this->errors !== null && count($this->errors) > 0) {
            $data['errors'] = $this->errors;
        }

        return $data;
    }
}
