<?php

namespace Mllexx\IFS\DTO;

use DateTimeInterface;

/**
 * Class ProformaInvoice
 *
 * Represents a proforma invoice in the IFS system
 */
class ProformaInvoice extends BaseDTO
{
    /**
     * The attributes that are mass assignable
     *
     * @var array
     */
    protected array $fillable = [
        'proforma_invoice_id',
        'proforma_invoice_no',
        'customer_id',
        'customer_no',
        'customer_name',
        'invoice_date',
        'due_date',
        'currency_code',
        'total_amount',
        'total_tax_amount',
        'total_amount_incl_tax',
        'status',
        'reference',
        'notes',
        'terms_conditions',
        'valid_until',
        'created_at',
        'updated_at',
        'lines',
    ];

    /**
     * The attributes that should be cast
     *
     * @var array
     */
    protected array $casts = [
        'proforma_invoice_id' => 'string',
        'invoice_date' => 'datetime',
        'due_date' => 'datetime',
        'valid_until' => 'datetime',
        'total_amount' => 'float',
        'total_tax_amount' => 'float',
        'total_amount_incl_tax' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'lines' => 'array',
    ];

    /**
     * Get the proforma invoice ID
     *
     * @return string|null
     */
    public function getProformaInvoiceId(): ?string
    {
        return $this->proforma_invoice_id;
    }

    /**
     * Get the proforma invoice number
     *
     * @return string|null
     */
    public function getProformaInvoiceNo(): ?string
    {
        return $this->proforma_invoice_no;
    }

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
    public function getCustomerName(): ?string
    {
        return $this->customer_name;
    }

    /**
     * Get the invoice date
     *
     * @return DateTimeInterface|null
     */
    public function getInvoiceDate(): ?DateTimeInterface
    {
        return $this->invoice_date;
    }

    /**
     * Get the due date
     *
     * @return DateTimeInterface|null
     */
    public function getDueDate(): ?DateTimeInterface
    {
        return $this->due_date;
    }

    /**
     * Get the valid until date
     *
     * @return DateTimeInterface|null
     */
    public function getValidUntil(): ?DateTimeInterface
    {
        return $this->valid_until;
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
     * Get the total amount (excluding tax)
     *
     * @return float
     */
    public function getTotalAmount(): float
    {
        return (float) $this->total_amount;
    }

    /**
     * Get the total tax amount
     *
     * @return float
     */
    public function getTotalTaxAmount(): float
    {
        return (float) $this->total_tax_amount;
    }

    /**
     * Get the total amount including tax
     *
     * @return float
     */
    public function getTotalAmountInclTax(): float
    {
        return (float) $this->total_amount_incl_tax;
    }

    /**
     * Get the status
     *
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * Get the reference
     *
     * @return string|null
     */
    public function getReference(): ?string
    {
        return $this->reference;
    }

    /**
     * Get the notes
     *
     * @return string|null
     */
    public function getNotes(): ?string
    {
        return $this->notes;
    }

    /**
     * Get the terms and conditions
     *
     * @return string|null
     */
    public function getTermsConditions(): ?string
    {
        return $this->terms_conditions;
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
     * Get the proforma invoice lines
     *
     * @return array
     */
    public function getLines(): array
    {
        return $this->lines ?? [];
    }

    /**
     * Convert the proforma invoice to an array
     *
     * @return array
     */
    public function toArray(): array
    {
        $data = parent::toArray();

        // Format dates as strings for API compatibility
        foreach (['invoice_date', 'due_date', 'valid_until', 'created_at', 'updated_at'] as $dateField) {
            if (isset($data[$dateField]) && $data[$dateField] instanceof DateTimeInterface) {
                $data[$dateField] = $data[$dateField]->format('Y-m-d');
            }
        }

        // Ensure lines is always an array
        $data['lines'] = $this->getLines();

        return $data;
    }
}
