<?php

declare(strict_types=1);

namespace App\Presentation\Http\Request;

use App\Domain\Exception\InvalidArgumentException;

final readonly class PayConfirmRequest
{
    /**
     * @param array{amount: string, currency: string} $amount
     */
    public function __construct(
        public string $paymentReference,
        public array $amount,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        if (empty($data['paymentReference']) || !is_string($data['paymentReference'])) {
            throw new InvalidArgumentException('Missing or invalid "paymentReference".');
        }

        if (!isset($data['amount']) || !is_array($data['amount']) || !isset($data['amount']['amount'])) {
            throw new InvalidArgumentException('Missing or invalid "amount".');
        }

        return new self(
            paymentReference: $data['paymentReference'],
            amount: [
                'amount' => (string) $data['amount']['amount'],
                'currency' => (string) ($data['amount']['currency'] ?? 'BRL'),
            ],
        );
    }
}
