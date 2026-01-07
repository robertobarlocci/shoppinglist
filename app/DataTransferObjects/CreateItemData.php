<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Enums\ListType;

final readonly class CreateItemData
{
    public function __construct(
        public string $name,
        public int $quantity,
        public ?int $categoryId,
        public ListType $listType,
        public int $createdBy,
    ) {}

    /**
     * Create from validated request data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, int $userId): self
    {
        return new self(
            name: $data['name'],
            quantity: $data['quantity'] ?? 1,
            categoryId: $data['category_id'] ?? null,
            listType: ListType::from($data['list_type'] ?? ListType::TO_BUY->value),
            createdBy: $userId,
        );
    }

    /**
     * Convert to array for model creation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'quantity' => $this->quantity,
            'category_id' => $this->categoryId,
            'list_type' => $this->listType,
            'created_by' => $this->createdBy,
        ];
    }
}
