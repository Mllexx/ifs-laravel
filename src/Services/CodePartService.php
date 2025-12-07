<?php

namespace Mllexx\IFS\Services;
use Mllexx\IFS\DTO\Invoice;
use Mllexx\IFS\Exceptions\IFSException;
use Mllexx\IFS\Http\IFSClient;
use Illuminate\Support\Facades\Log;

class CodePartService
{

    protected IFSClient $client;

    protected array $endpoints = [
        'vessels' => "/CodePartValuesHandling.svc/CompanyFinanceSet(Company='<company>')/CodeIArray",
    ];

    protected array $params = [
        'vessels' => [
            '$filter' => "CodePart eq 'VESSEL'",
            '$select' => "CodeValue,Description",
        ],
    ];

    protected array $requiredFields = [
        'vessels' => [
            'Company'=> null,
            'CodePart' => 'I',
            'CodeI' => null,//vessel name goes here
            'Description'=> null,
            'ValidFrom'=> null, //date format 'YYYY-MM-DD'
            'ValidUntil' => null, //date format 'YYYY-MM-DD'
            'BudgetValueDb' => false,
        ],
    ];

    public function __construct()
    {
        $this->client = new IFSClient();
    }

    
    public function create(mixed $data,string $company)
    {
        Log::info("Creating Code Part Value in IFS for company: $company");
        $endpoint = str_replace('<company>',$company,$this->endpoints['vessels']);
        //
        $payload = $this->requiredFields['vessels'];
        Log::info("Compiling payload ".json_encode($data));

        foreach ($payload as $field => $defaultValue) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
            
        }
        if( array_key_exists('Company',$payload) ){
            $payload['Company'] = $company;
        }
        //
        Log::info("Invoking IFS API: ".json_encode($payload));
        $response = $this->client->post($endpoint, $payload);
        Log::info("Received response with status code: " . $response->getStatusCode());
        if ($response->getStatusCode() !== 201) {
            Log::error("Failed to create Code Part Value. Status Code: " . $response->getStatusCode());
            throw new IFSException("Failed to create Code Part Value. Status Code: " . $response->getStatusCode());
        }
        return $response;
    }
    //no additional logic is needed at the moment
    //if additional API functions such as resource creation/update/deletion are needed, they will be added
}