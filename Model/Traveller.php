<?php

namespace Omnistay\Model;

/** A guest as a booking names them; the first one is the contact. */
final readonly class Traveller
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $title = null,
    ) {
    }

    public function name(): string
    {
        return trim($this->firstName.' '.$this->lastName);
    }

    /** @return array<string, ?string> */
    public function toArray(): array
    {
        return ['firstName' => $this->firstName, 'lastName' => $this->lastName, 'email' => $this->email, 'phone' => $this->phone, 'title' => $this->title];
    }
}
