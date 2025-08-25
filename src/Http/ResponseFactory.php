<?php

namespace Mllexx\IFS\Http;

use Mllexx\IFS\DTO\ApiResponse;
use Mllexx\IFS\DTO\Customer;
use Mllexx\IFS\DTO\Invoice;
use Mllexx\IFS\DTO\ProformaInvoice;
use Mllexx\IFS\Exceptions\IFSException;

class ResponseFactory
{
    /**
     * Create an API response from a Guzzle response
     *
     * @param array $responseData
     * @param array $headers
     * @param int $statusCode
     * @return ApiResponse
     */
    public static function createApiResponse(
        array | object $responseData,
        array $headers = [],
        int $statusCode = 200
    ): ApiResponse {
        $success = $statusCode >= 200 && $statusCode < 300;
        $message = $responseData['message'] ?? null;
        $data = $responseData['value'] ?? $responseData;
        $meta = $responseData['meta'] ?? [];

        return new ApiResponse($success, $data, $message, $meta, $headers, $statusCode);
    }

    /**
     * Create a Customer DTO from response data
     *
     * @param array $data
     * @return Customer
     * @throws IFSException
     */
    public static function createCustomer(array $data): Customer
    {
        return new Customer($data);
    }

    /**
     * Create an array of Customer DTOs from response data
     *
     * @param array $items
     * @return Customer[]
     * @throws IFSException
     */
    public static function createCustomerCollection(array $items): array
    {
        $customers = [];

        foreach ($items as $item) {
            $customers[] = self::createCustomer($item);
        }

        return $customers;
    }

    /**
     * Create an Invoice DTO from response data
     *
     * @param array $data
     * @return Invoice
     * @throws IFSException
     */
    public static function createInvoice(array $data): Invoice
    {
        return new Invoice($data);
    }

    /**
     * Create an array of Invoice DTOs from response data
     *
     * @param array $items
     * @return Invoice[]
     * @throws IFSException
     */
    public static function createInvoiceCollection(array $items): array
    {
        $invoices = [];

        foreach ($items as $item) {
            $invoices[] = self::createInvoice($item);
        }

        return $invoices;
    }

    /**
     * Create a ProformaInvoice DTO from response data
     *
     * @param array $data
     * @return ProformaInvoice
     * @throws IFSException
     */
    public static function createProformaInvoice(array $data): ProformaInvoice
    {
        return new ProformaInvoice($data);
    }

    /**
     * Create an array of ProformaInvoice DTOs from response data
     *
     * @param array $items
     * @return ProformaInvoice[]
     * @throws IFSException
     */
    public static function createProformaInvoiceCollection(array $items): array
    {
        $invoices = [];

        foreach ($items as $item) {
            $invoices[] = self::createProformaInvoice($item);
        }

        return $invoices;
    }

    /**
     * Extract pagination data from the response
     *
     * @param array $responseData
     * @return array
     */
    public static function extractPaginationData(array $responseData): array
    {
        return [
            'total' => $responseData['meta']['total'] ?? 0,
            'per_page' => $responseData['meta']['per_page'] ?? 0,
            'current_page' => $responseData['meta']['current_page'] ?? 1,
            'last_page' => $responseData['meta']['last_page'] ?? 1,
            'from' => $responseData['meta']['from'] ?? 0,
            'to' => $responseData['meta']['to'] ?? 0,
        ];
    }
}
