<?php

namespace Mllexx\IFS\Services;

use Mllexx\IFS\Exceptions\IFSException;
use Mllexx\IFS\Http\IFSClient;
use Illuminate\Support\Facades\Log;

class VesselService
{
    // Vessel service implementation goes here
    /**
     * The HTTP client instance
     *
     * @var IFSClient
     */
    protected IFSClient $client;

    protected array $endpoints = [
        'Vessel'=>[
            'head'=>"/CodePartValuesHandling.svc/CompanyFinanceSet(Company='<company>')/CodeIArray",
        ]
    ];

    protected array $requiredFields = [
        'Vessel'=>[
            'Company'=>'string',
            'CodePart'=>'string',
            'CodeI'=>'string',
            'Description'=>'string',
            'ValidFrom'=>'string',
            'ValidUntil'=>'string',
            'BudgetValueDb'=>'boolean',
        ]
    ];

    public function __construct(IFSClient $client)
    {
        $this->client = $client;
    }

    public function create(array $data)
    {
        // Implementation for creating a vessel
        $$validatedData = $this->validateData($data);
        //add logic for creating response factory
        return $this->client->post($this->endpoints['Vessel']['head'], $validatedData);
    }

    public function list(array $params = [])
    {
        // Implementation for listing vessels
        return $this->client->get($this->endpoints['Vessel']['head'], $params);
    }

    private function validateData(array $data)
    {
        // Implementation for validating vessel data
        return $data;

    }

}