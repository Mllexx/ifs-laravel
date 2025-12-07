<?php

namespace Mllexx\IFS\Services;

use Mllexx\IFS\DTO\Invoice;
use Mllexx\IFS\Exceptions\IFSException;
use Mllexx\IFS\Http\IFSClient;
use Illuminate\Support\Facades\Log;
use App\Bulkstream\IFS\TaxService;

class InvoiceService
{
    CONST DEFAULT_TAX_RATE = 16.0;
    /**
     * The HTTP client instance
     *
     * @var IFSClient
     */
    protected IFSClient $client;

    /**
     * The base endpoint for invoice operations
     *
     * @var array
     */
    protected array $endpoints = [
        'Instant'=>[
            'head'=>'/InstantInvoiceHandling.svc/InstantInvoiceSet',
            'line'=>"/InstantInvoiceHandling.svc/InstantInvoiceSet(Company='<company>',InvoiceId=<invoice_id>)/InvoiceItemArray",
            'notes'=>"/InstantInvoiceHandling.svc/NotesInfoVirtuals(Objkey='<Objkey>')/FinNotesArray",
            'taxlines'=>"/InstantInvoiceHandling.svc/SourceLines(Objkey='<Objkey>')/TaxItems",
            'taxlinetable'=>"/InstantInvoiceHandling.svc/SourceLines(Objkey='<ParentObjkey>')/IfsApp.InstantInvoiceHandling.SourceLineVirtual_SaveToSrcTaxItemTable",
            'objectKeys'=>[
                'notes'=>'/InstantInvoiceHandling.svc/NotesInfoVirtuals',
                'lineitems'=>'/InstantInvoiceHandling.svc/SourceLines'
            ]
        ],
        'Customer'=>[
            'head'=>'/CustomerOrderInvoiceHandling.svc/CustomerOrderInvHeadSet',
            'line'=>'/CustomerOrderInvoiceHandling.svc/CustomerOrderInvLineSet',
        ]
    ];

    protected array $requiredFields = [
        'Instant'=>[
            'Company'=>'string',
            //'InvoiceId'=>'number',
            'Identity'=>'string',
            'PartyType'=>'enum["Company" "Customer" "Supplier" "Person" "Manufacturer" "Owner" "ForwardingAgent" "Employee"]',
            'InvoiceDate'=>'string',
            'DueDate'=>'string',
            'Creator'=>'string',
            'CurrRate'=>'number',
            'InvoiceType'=>'string',
            'PayTermId'=>'string',
            'CreationDate'=>'string',
            'Currency'=>'string',
            'OperationalKey'=>'boolean',
            'OutInvVouDateBase'=>'string',
            'OutInvCurrRateBase'=>'string',
            'TaxSellCurrRateBase'=>'string',
            'IntAllowed'=>'string',
            'AffBaseLedgPost'=>'string',
            'TaxCurrRate'=>'number',
        ],
        'Customer'=>[
            
        ]
    ];

    protected $endpoint;

    protected string $type = 'Customer';
    /**
     * Create a new InvoiceService instance
     *
     * @param IFSClient $client
     */
    public function __construct(IFSClient $client, $type = 'Customer')
    {
        $this->client = $client;
        $this->type = $type;
        $this->endpoint = $this->endpoints[$type];
    }

    /**
     * Get an invoice by ID
     *
     * @param string $invoiceId
     * @return Invoice
     * @throws IFSException
     */
    public function find(string $invoiceId): Invoice
    {
        $response = $this->client->get("{".$this->endpoint['head']."}"."/".$invoiceId);

        return $this->client
                    ->getResponseFactory()
                    ->createInvoice($response->getData());
    }

    /**
     * List all invoices with optional filters
     *
     * @param array $filters
     * @return array
     * @throws IFSException
     */
    public function list(array $filters = [],$batchSize=15): array
    {
        $filters['$top']=$batchSize;
        $response = $this->client->get($this->endpoint['head'].'?$top='.$batchSize, $filters);

        return $this->client
                    ->getResponseFactory()
                    ->createInvoiceCollection($response->getData() ?? []);
    }

    /**
     * Create a new invoice
     *
     * @param array $data
     * @return Invoice
     * @throws IFSException
     */
    public function create(mixed $data,$notes = null): Invoice
    {
        $validationResponse = $this->validatePostData($data);
        $response = $this->client->post($this->endpoint['head'], $data);
        $erpInvoice = $response->getData();
        //if invoice has notes add them
        if(!is_null($notes)){
            Log::info("Invoice has notes,adding. Invoice {$erpInvoice['InvoiceNo']} for company {$erpInvoice['Company']}");
            $this->addInvoiceNotes($erpInvoice['Company'],$erpInvoice['InvoiceNo'],$notes);
        }
        return $this->client
                    ->getResponseFactory()
                    ->createInvoice($erpInvoice);
    }

    /**
     * Add line item to invoice
     *
     * @param array $data
     * @return Invoice
     * @throws IFSException
     */
    public function createLineItem($company,$invoiceId,$data): Invoice
    {
        //$validationResponse = $this->validatePostData($data);
        $endpoint = str_replace('<company>',$company,$this->endpoint['line']);
        $endpoint = str_replace('<invoice_id>',$invoiceId,$endpoint);
        #
        $response = $this->client->post($endpoint, $data);
        $responseData = $response->getData();
        #
        Log::info("Adding tax lines to invoice item {$responseData['ItemId']} for invoice {$invoiceId} company {$company}");
        $this->addItemTaxLines($responseData);
        #
        return $this->client
                    ->getResponseFactory()
                    ->createInvoice($responseData);
    }

    /**
     * Add notes to invoice
     *
     * @param string $company
     * @param int $invoiceId
     * @param string $notes
     * @return array
     * @throws IFSException
     */
    public function addInvoiceNotes($company,$invoiceId,$notes){
        //Log::info("Adding notes to invoice {$invoiceId} for company {$company}");
        $objectData = $this->getInvoiceNotesObjectKey($company,$invoiceId);
        Log::info("Object key: {$objectData['Objkey']}");
        $endpoint = str_replace('<Objkey>',$objectData['Objkey'],$this->endpoints['Instant']['notes']);
        Log::info("Endpoint: {$endpoint}");
        $payload = [
            "ParentObjkey"=> $objectData['Objkey'],
            "Text"=> $notes
        ];
        Log::info("Payload: " . json_encode($payload));
        $response = $this->client->post($endpoint, $payload);
        return $response->getData();
    }

    /**
     * Add tax lines to invoice item
     *
     * @param object $invoiceItem
     * @return array
     * @throws IFSException
     */
    public function addItemTaxLines($invoiceItem){
        if(is_array($invoiceItem)){
            $invoiceItem = (object) $invoiceItem;  
        }

        $lineTaxObject = TaxService::getTax($invoiceItem->VatCode); // change to use laravel ifs tax service
        $itemReference = $data->keyref ?? ("COMPANY=".($invoiceItem->Company ?? '')."^INVOICE_ID=".$invoiceItem->InvoiceId."^LINE_NO=".$invoiceItem->ItemId."^");
        Log::info("Adding invoice item tax line. Item Reference: {$itemReference}");
        //get object key for line item
        $itemObjKey = $this->getInvoiceLineObjectKey($itemReference);
        $endpoint = str_replace('<Objkey>',$itemObjKey['Objkey'],$this->endpoints['Instant']['taxlines']);
        Log::info("Endpoint: {$endpoint}");
        $payload = [
            "ParentObjkey"=> $itemObjKey['Objkey'],
            "Company"=> $invoiceItem->Company,
            "TaxCode"=> $invoiceItem->VatCode,   
            "TaxPercentage"=> (double)$lineTaxObject->rate ?? self::DEFAULT_TAX_RATE,
            //defaults
            "TaxType"=> "Tax",
            "TaxPercentageEditable" => false,
            "TaxCurrAmountEditable" => false,
            "TaxBaseCurrAmountEditable" => false,
            "NonDedTaxCurrAmtEditable" => false,
            "TotalTaxCurrAmountEditable" => false,
            "CstCodeEditable" => false,
            "LegalTaxClassEditable" => false,
            "BenefitCodeEditable" => false,
            "CitationEditable" => false,
            "ReductionEditable" => false,
            "ValueAddedMarginEditable" => false,
            "DeferralEditable" => false,
            "DeferralBaseEditable" => false,
            "DeferralAmountEditable" => false,
            "TaxBaseModeEditable" => false,
            "TaxReliefReasonEditable" => false,
            "TaxTypeCategoryEditable" => false,
            //amounts
            "TaxCurrAmount" => $invoiceItem->VatCurrAmount,
            "TaxDomAmount" => $invoiceItem->VatDomAmount,
            //"TaxParallelAmount" => 0,
            "TaxBaseCurrAmount" => $invoiceItem->NetCurrAmount,
            "TaxBaseDomAmount" => $invoiceItem->NetCurrAmount,
            //"TaxBaseParallelAmount" => 0,
            //"NonDedTaxCurrAmount" => 0,
            //"NonDedTaxDomAmount" => 0,
            //"NonDedTaxParallelAmount" => 0,
            "TotalTaxCurrAmount"=>$invoiceItem->VatCurrAmount,
            "TotalTaxDomAmount"=>$invoiceItem->VatDomAmount,
            //"TotalTaxParallelAmount" => 0
        ];
        //Log::info("Payload: " . json_encode($payload));
        $response = $this->client->post($endpoint, $payload);
        $data = $response->getData();
        #
        if($response->isSuccessful()){
            //save tax line to tax table
            $this->_itemTaxLineObject($data['ParentObjkey']);
            Log::info("Successfully added tax line to invoice item {$invoiceItem->InvoiceId}");
        }
        return $data;
    }

    /**
     * Update an existing invoice
     *
     * @param string $invoiceId
     * @param array $data
     * @return Invoice
     * @throws IFSException
     */
    public function update(string $invoiceId, array $data): Invoice
    {
        $response = $this->client->put("{$this->endpoint['head']}/{$invoiceId}", $data);

        return $this->client
                    ->getResponseFactory()
                    ->createInvoice($response->getData());
    }

    /**
     * Delete an invoice
     *
     * @param string $invoiceId
     * @return bool
     * @throws IFSException
     */
    public function delete(string $invoiceId): bool
    {
        $response = $this->client->delete("{$this->endpoint['head']}/{$invoiceId}");

        return $response->isSuccessful();
    }

    /**
     * Get invoices for a specific customer
     *
     * @param string $customerId
     * @param array $filters
     * @return array
     * @throws IFSException
     */
    public function getByCustomer(string $customerId, array $filters = []): array
    {
        $response = $this->client->get("customers/{$customerId}/invoices", $filters);

        return $this->client->getResponseFactory()
            ->createInvoiceCollection($response->getData()['data'] ?? []);
    }

    /**
     * Send an invoice to the customer
     *
     * @param string $invoiceId
     * @param array $options
     * @return bool
     * @throws IFSException
     */
    public function send(string $invoiceId, array $options = []): bool
    {
        $response = $this->client->post(
            "{$this->endpoint['head']}/{$invoiceId}/send",
            $options
        );

        return $response->isSuccessful();
    }

    /**
     * Mark an invoice as paid
     *
     * @param string $invoiceId
     * @param array $paymentData
     * @return bool
     * @throws IFSException
     */
    public function markAsPaid(string $invoiceId, array $paymentData = []): bool
    {
        $response = $this->client->post(
            "{$this->endpoint['head']}/{$invoiceId}/pay",
            $paymentData
        );

        return $response->isSuccessful();
    }

    /**
     * Download the PDF version of an invoice
     *
     * @param string $invoiceId
     * @return string|null
     * @throws IFSException
     */
    public function downloadPdf(string $invoiceId): ?string
    {
        $response = $this->client->get(
            "{$this->endpoint['head']}/{$invoiceId}/download",
            ['accept' => 'application/pdf']
        );

        return $response->getData();
    }

    /**
     * Get the pagination metadata from the last response
     *
     * @return array
     */
    public function getPagination(): array
    {
        return $this->client->getResponseFactory()
            ->extractPaginationData($this->client->getLastResponseData() ?? []);
    }


    protected function validatePostData(array $data): array
    {
        //check if data has required fields
        foreach ($this->requiredFields[$this->type] as $name => $type) {
            if (!isset($data[$name])) {
                throw new IFSException("Invoice creation-missing required field: {$name}");
            }
        }
        return $data;
    }

    protected function validateUpdateData(array $data): array
    {
        //check if data has required fields
        foreach ($this->requiredUpdateFields as $name => $type) {
            if (!isset($data[$name])) {
                throw new IFSException("Invoice update-missing required field: {$name}");
            }
        }
        return $data;
    }

    /**
     * Get the object key for invoice notes
     *
     * @param string $company
     * @param int $invoiceId
     * @return array
     * @throws IFSException
     */
    public function getInvoiceNotesObjectKey($company,$invoiceId){
        $payload = [
            "PackageName"=> "INVOICE_NOTE_API",
            "KeyRef"=> "COMPANY=".$company."^INVOICE_ID=".$invoiceId."^",
            "EntityName"=> "InstantInvoice",
            "CallingProjectionName"=> "InstantInvoiceHandling"
        ];
        $response = $this->client->post($this->endpoints['Instant']['objectKeys']['notes'], $payload);
        return $response->getData();
    }

    /**
     * Get the object key for invoice line items
     * @param string $company
     * @param int $invoiceId
     * @param int $lineNo
     * @return array
     * @throws IFSException
     */
    public function getInvoiceLineObjectKey($keyref,$company=null,$invoiceId=null,$lineNo=null){
        if(is_null($keyref)){
            if(is_null($company) || is_null($invoiceId) || is_null($lineNo)){
                throw new IFSException("To get line item object key, either provide keyref or (company,invoiceId and lineNo)");
            }
            $keyref = "COMPANY=".$company."^INVOICE_ID=".$invoiceId."^ITEM_ID=".$lineNo."^";
        }else{
            $keyref = str_replace('LINE_NO','ITEM_ID',$keyref);
        }
        Log::info("Getting line item object key for keyref: {$keyref}");
        $payload = [
            "KeyRef"=> $keyref,
            "PackageName"=> "INSTANT_INVOICE_API",
        ];
        $response = $this->client->post($this->endpoints['Instant']['objectKeys']['lineitems'], $payload);
        $responseData = $response->getData();
        Log::info("Received line item object key:".json_encode($responseData));
        return $responseData;
    }   

    private function _itemTaxLineObject($ParentObjkey){
        Log::info("Saving tax line to tax table for item object key: {$ParentObjkey}");
        $endpoint = str_replace('<ParentObjkey>',$ParentObjkey,$this->endpoints['Instant']['taxlinetable']);
        Log::info("Endpoint: {$endpoint}");
        $response = $this->client->post($endpoint);
        return $response->getData();
    }
}