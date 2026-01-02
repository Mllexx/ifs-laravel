<?php
//
//TODO: Add logic to support local sync
//TODO: Local sync involves creating a db table and saving data on local DB e.g ifs_sales_objects
//
namespace Mllexx\IFS\Services;

use Carbon\Carbon;
use Mllexx\IFS\Exceptions\IFSException;
use Mllexx\IFS\Http\IFSClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\IFSSalesObjects;
use Mllexx\IFS\IFS;

use function Symfony\Component\Clock\now;

class SalesObjectService{

    /**
     * The HTTP client instance
     *
     * @var IFSClient
     */
    protected IFSClient $client;

    /**
     * The base endpoint for sales objects operations
     *
     * @var array
     */
    protected array $endpoints = [
        'salesObjects' => "/SalesObjectsHandling.svc/SalesObjectSet",
    ];

    /*
    protected array $params = [
        'salesObjects' => [
            '$filter' => "Company eq '<company>'",
        ],
    ];
    */

    protected $companyCode;

    public function __construct($companyCode=null)
    {
        $this->client = new IFSClient();
        $this->companyCode = is_null($companyCode)? env('IFS_DEFAULT_COMPANY_CODE') : $companyCode ;
    }


    public function getSalesObjects(){
        Log::info("Fetching sales objects for company [$this->companyCode]");
        $endpoint = $this->addFilters($this->endpoints['salesObjects']);
        Log::info("Calling the enpoint [$endpoint]");
        $response = $this->client->get($endpoint);
        if($response->isSuccessful()){
            Log::info("Sales objects fetched successfully");
            return $response->getData();
        }else{
            Log::error("Failed to fetch sales objects for company [$this->companyCode]. Error: ". $response->getMessage());
            return [];
        }
    }

    public function syncSalesObjects($overwrite=false){
        Log::info("Syncing sales objects for company [$this->companyCode]");
        try{
            $list = $this->getSalesObjects();
            DB::beginTransaction();
            if($overwrite){
                Log::info("Clearing existing sales objects from database");
                IFSSalesObjects::where('company_code',$this->companyCode)->delete();
            }
            Log::info("Sales objects fetched: " . count($list));
            $lastSyncedAt = Carbon::now();
            foreach($list as $item){
                try {
                    // Check if sales object already exists
                    $existing = IFSSalesObjects::where('object_code', $item['ObjectId'])
                        ->where('company_code', $this->companyCode)
                        ->first();
                    if ($existing) {
                        Log::info("Sales object with code {$item['ObjectId']} already exists. Skipping.");
                        continue;
                    }
                    //    
                    $salesObject = (object)$item; 
                    IFSSalesObjects::updateOrCreate([
                        'object_code' => $salesObject->ObjectId,
                        'description' => $salesObject->Description,
                        'has_tax' => $salesObject->Taxable ?? false,
                        'tax_code' => $salesObject->TaxCode,
                        'price' => $salesObject->Price,
                        'account' => $salesObject->CodeA,
                        'company_code' => $salesObject->Company,
                        'uom' => $salesObject->UnitOfMeasure,
                        'created_at' => now(),
                    ]);
                } catch (\Exception $e) {
                    report($e);
                    if ($e->getCode() == 23505) { // Integrity constraint violation
                        Log::warning("Sales object with code {$item['ObjectId']} already exists. Skipping.");
                        continue;
                    }
                    Log::error("Error checking existing sales object: " . $e->getMessage());
                }
            }
            DB::commit();
            Log::info("Sales objects synced successfully");
            return [
                'success'=>true,
                'message' => "Sales objects synced successfully"
            ];
        }catch(\Exception $e){
            DB::rollBack();
            Log::error("Error syncing sales objects: " . $e->getMessage());
            report($e);
            return [
                'success' => false,
                'message' => "Error syncing sales objects: " . $e->getMessage()
            ];
        }
    }

    private function addFilters($endpoint)
    {
        return $endpoint.'?$filter=Company eq '."'".$this->companyCode."'";
    }

}