<?php

namespace Omnistay\Model;

/** A picture of a property, where its provider serves it, with the credit its licence asks for. */
final readonly class Photo
{
    public function __construct(
        public string $url,
        public ?string $caption = null,
        public ?int $width = null,
        public ?int $height = null,
        public ?string $credit = null,
        public ?string $category = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['url' => $this->url, 'caption' => $this->caption, 'width' => $this->width, 'height' => $this->height, 'credit' => $this->credit, 'category' => $this->category];
    }
}
