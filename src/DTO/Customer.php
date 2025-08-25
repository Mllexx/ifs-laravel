<?php

namespace Mllexx\IFS\DTO;

use DateTimeInterface;

/**
 * Class Customer
 *
 * Represents a customer in the IFS system
 */
class Customer extends BaseDTO
{
    
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
        return $this->fill($attributes);
    }

    /**
     * The attributes that are mass assignable
     *
     * @var array
     */
    protected array $fillable = [
        'customer_id',
        'name',
        'creation_date',
        'party',
        'default_domain',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast
     *
     * @var array
     */
    protected array $casts = [
        'default_domain' => 'boolean',
        'one_time' => 'boolean',
        'b2b_customer' => 'boolean',
        'valid_data_processing_purpose' => 'boolean',
    ];

    /**
     * Get the customer ID
     *
     * @return string|null
     */
    public function getCustomerId(): ?string
    {
        return $this->customer_id;
    }

    /**
     * Get the customer number
     *
     * @return string|null
     */
    public function getCustomerNo(): ?string
    {
        return $this->customer_no;
    }

    /**
     * Get the customer name
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Get the company name
     *
     * @return string|null
     */
    /*
    public function getCompanyName(): ?string
    {
        return $this->company_name;
    }
    */

    /**
     * Get the email address
     *
     * @return string|null
     */
    /*
    public function getEmail(): ?string
    {
        return $this->email;
    }
    */

    /**
     * Get the phone number
     *
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * Get the VAT number
     *
     * @return string|null
     */
    public function getVatNo(): ?string
    {
        return $this->vat_no;
    }

    /**
     * Get the currency code
     *
     * @return string|null
     */
    public function getCurrencyCode(): ?string
    {
        return $this->currency_code;
    }

    /**
     * Get the credit limit
     *
     * @return float|null
     */
    public function getCreditLimit(): ?float
    {
        return $this->credit_limit;
    }


    /**
     * Check if the customer is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    /**
     * Get the creation date
     *
     * @return DateTimeInterface|null
     */
    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->created_at;
    }

    /**
     * Get the last update date
     *
     * @return DateTimeInterface|null
     */
    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updated_at;
    }

    /**
     * Convert the customer to an array
     *
     * @return array
     */
    public function toArray(): array
    {
        $data = parent::toArray();

        // Format dates as strings for API compatibility
        foreach (['created_at', 'updated_at'] as $dateField) {
            if (isset($data[$dateField]) && $data[$dateField] instanceof DateTimeInterface) {
                $data[$dateField] = $data[$dateField]->format('c');
            }
        }

        return $data;
    }
}
