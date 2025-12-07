<?php

namespace Mllexx\IFS\DTO;

class TaxCode extends BaseDTO
{
    public string $code;
    public string $description;
    public float $rate;
    public string $company;
    public string $taxCategory;
    public bool $active;
    public ?string $validFrom;
    public ?string $validTo;

    public function __construct(array $data)
    {
        $this->code = $data['code'] ?? '';
        $this->description = $data['description'] ?? '';
        $this->rate = (float)($data['rate'] ?? 0.0);
        $this->company = $data['company'] ?? '';
        $this->taxCategory = $data['tax_category'] ?? '';
        $this->active = $data['active'] ?? true;
        $this->validFrom = $data['valid_from'] ?? null;
        $this->validTo = $data['valid_to'] ?? null;
    }
}