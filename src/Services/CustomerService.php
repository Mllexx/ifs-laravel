<?php

namespace Mllexx\IFS\Services;

use Mllexx\IFS\DTO\Customer;
use Mllexx\IFS\Exceptions\IFSException;
use Mllexx\IFS\Http\IFSClient;
use App\Models\IFSCustomers;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    /**
     * The HTTP client instance
     *
     * @var IFSClient
     */
    protected IFSClient $client;



    /**
     * The base endpoint for customer operations
     *
     * @var string
     */
    protected array $endpoints = [
        'list'=>'/CustomersHandling.svc/CustomerInfoSet',
        'single'=>"/CustomersHandling.svc/CustomerInfoSet(CustomerId='<customer_id>')",
        'aggregated'=>"QuickReports.svc/QuickReport_604444(CompanyId='<company_id>')",
    ];

    /**
     * Create a new CustomerService instance
     *
     * @param IFSClient $client
     */
    public function __construct(IFSClient $client=null)
    {
        if(is_null($client) || !isset($client)) {
            $client = new IFSClient();
        }
        $this->client = $client;
    }

    /**
     * Get a customer by ID
     *
     * @param string $customerId
     * @return Customer
     * @throws IFSException
     */

    function find(string $customerId): Customer
    {
        $endpoint = str_replace('<customer_id>', $customerId, $this->endpoints['single']);
        $response = $this->client->get($endpoint);

        return $this->client->getResponseFactory()
            ->createCustomer($response->getData());
    }

    /**
     * List all customers with optional filters
     *
     * @param array $filters
     * @return array
     * @throws IFSException
     */
    public function list(array $filters = [],$batchSize=15): array
    {
        $endpoint = $this->endpoints['list'].'?'.$this->startsWithFilter('CustomerId','GB-');
        $endpoint .= '&'.$this->selectCols();

        $response = $this->client->get($endpoint);
        return $this->client
                    ->getResponseFactory()
                    ->createCustomerCollection($response->getData() ?? []);
    }

    /**
     * Fetch aggregated customer data
     *
     * @param string $companyId
     * @param array $filters
     * @return array
     * @throws IFSException
     */
    public function listAggregated(string $companyId='GBHL',array $filters = []): array
    {
        $endpoint = str_replace('<company_id>',$companyId,$this->endpoints['aggregated']);
        $response = $this->client->get($endpoint);
        $aggergateList =  $this->client->getResponseFactory()->createCustomerCollection($response->getData() ?? []);
        $cleanedList = [];
        //clean up list
        foreach($aggergateList as $customer){
            foreach($customer as $key => $value){
                if(is_null($value)){
                    $customer->$key = '';
                }
                if( preg_match('/^C[1-9]/', $value)) {
                    $new_value = preg_replace('/^C[1-9]/', '', $value);
                }
                $customer[$key] = $new_value;
            }
            $cleanedList[] = $customer;
        }
        return $cleanedList;
    }

    /**
     * Create a new customer
     *
     * @param array $data
     * @return Customer
     * @throws IFSException
     */
    public function create(array $data): Customer
    {
        $response = $this->client->post($this->endpoints['list'], $data);

        return $this->client->getResponseFactory()
            ->createCustomer($response->getData());
    }

    /**
     * Update an existing customer
     *
     * @param string $customerId
     * @param array $data
     * @return Customer
     * @throws IFSException
     */
    public function update(string $customerId, array $data): Customer
    {
        $endpoint = str_replace('<customer_id>', $customerId, $this->endpoints['single']);
        $response = $this->client->put($endpoint, $data);

        return $this->client->getResponseFactory()
            ->createCustomer($response->getData());
    }

    /**
     * Delete a customer
     *
     * @param string $customerId
     * @return bool
     * @throws IFSException
     */
    public function delete(string $customerId): bool
    {
        $endpoint = str_replace('<customer_id>', $customerId, $this->endpoints['single']);
        $response = $this->client->delete($endpoint);

        return $response->isSuccessful();
    }

    /**
     * Search for customers by name, email, or other criteria
     *
     * @param string $query
     * @param array $filters
     * @return array
     * @throws IFSException
     */
    public function search(string $query, array $filters = []): array
    {
        $filters['q'] = $query;

        // Build search filter
        $endpoint = $this->endpoints['list'] . '?' . $this->containsFilter('Name', $query);
        foreach ($filters as $key => $value) {
            $endpoint .= '&' . $this->containsFilter($key, $value);
        }
        
        $response = $this->client->get($endpoint);

        return $this->client->getResponseFactory()
            ->createCustomerCollection($response->getData() ?? []);
    }

    /**
     * Get the pagination metadata from the last response
     *
     * @return array
     */
    /**
     * Get the total count from response metadata
     * 
     * @return int
     */
    public function getTotalCount(): int
    {
        $response = $this->client->get($this->endpoints['list'] . '/$count');
        return intval($response->getData() ?? 0);
    }


    protected function addFiltersToQuery(array $filters): string
    {
        if (empty($filters)) {
            return '';
        }

        $queryParts = [];
        foreach ($filters as $key => $value) {
            $queryParts[] = "{$key}=" . urlencode($value);
        }

        return '?' . implode('&', $queryParts);
    }

    /**
     * Filter helpers
     */
    protected function startsWithFilter(string $fieldName,string $filterString){
        return "$"."filter=startswith($fieldName,'$filterString')";
    }

    protected function containsFilter(string $fieldName,string $filterString){
        return "$"."filter=contains($fieldName,'$filterString')";
    }

    /**
     * Select specific columns for customer data
     *
     * @param array|null $columns
     * @return string
     */
    protected function selectCols(array|null $columns = null): string
    {
        if(is_null($columns) || empty($columns)){
            $columns = [
                'CustomerId',
                'Name',
                'Company',
                'Party',
                'PartyType',
                'Identiy',
                'Country',
                //'B2bCustomer',
                //'CorporateForm',
                'CreationDate',
                'CurrencyCode'
            ];
        }
        return '$select=' . implode(',', $columns);
    }

    /**
     * Sync customers from IFS to local database
     *
     * @param int $companyId
     * @return array
     */
    public function syncCustomers($companyId=1,$overwrite=false){
        Log::info("Syncing customers from IFS");
        //TODO: Implement batching if there are many customers
        //TODO: Implement last synced at to only get new/updated customers
        //TODO: Implement logic to check existence of sync tables if not exists create them (run migrations if not exists)
        try{
            //$customers = $this->list();
            $customers = $this->listAggregated();
            DB::beginTransaction();
            if($overwrite){
                Log::info("Clearing existing customers from database");
                IFSCustomers::where('company_id',$companyId)->delete();
            }
            Log::info("Adding customers to database");
            $lastSyncedAt = Carbon::now();
            foreach ($customers as $customer) {
                try {
                    // Check if customer already exists
                    $existing = IFSCustomers::where('customer_id', $customer->C2_CUSTOMER_ID)
                        ->where('company_id', $companyId)
                        ->first();
                    if ($existing) {
                        Log::info("Customer with ID {$customer->C2_CUSTOMER_ID} already exists. Skipping.");
                        continue;
                    }
                    //
                    IFSCustomers::updateOrCreate([
                        'company_id' => $companyId,
                        'customer_id' => $customer->C2_CUSTOMER_ID,
                        'name' => $customer->C3_CUSTOMER_NAME,
                        //'party' => $customer->C4_PARTY_TYPE,
                        'country' => $customer->C8_COUNTRY,
                        //'b2b_customer' => $customer->B2bCustomer,
                        //'corporate_form' => $customer->CorporateForm,
                        'creation_date' => $customer->C7_CREATION_DATE, 
                        'currency_code' => $customer->C5_CURRENCY, 
                        'last_synced_at' => $lastSyncedAt,
                    ]);
                } catch (\Exception $e) {
                    report($e);
                    if ($e->getCode() == 23505) { // Integrity constraint violation
                        Log::warning("Customer with code {$customer->C2_CUSTOMER_ID} already exists. Skipping.");
                        continue;
                    }
                    Log::error("Error checking existence for customer ID {$customer->C2_CUSTOMER_ID}: " . $e->getMessage());
                }
            }
            DB::commit();
            Log::info("Customers added to database");
            return [
                'success' => true,
                'message' => "IFS Customers synced successfully.",
            ];
        }catch(\Exception $e){
            DB::rollBack();
            Log::error("Failed to sync customers. Error: ". $e->getMessage());
            report($e);
            return [
                'success' => false,
                'message' => 'Failed to sync IFS Customers. Error: '. $e->getMessage(),
            ];  
        }
    }
}
