<?php

namespace Mllexx\IFS\Http\Controllers;

use Illuminate\Http\Request;
use Mllexx\IFS\Http\IFSClient;
use Mllexx\IFS\Services\CustomerService;
use Mllexx\IFS\Services\InvoiceService;
use Mllexx\IFS\Services\SalesObjectService;
use Mllexx\IFS\Services\CurrencyInfoService;
use App\Models\Tariff;
use App\Models\BillableItem;
use App\Models\ChargeAttribute;

class TestController
{
    public $invoicePayload = [
        "Company" => "GBHL",
        "Identity" => "GB-GIL01K",
        "PartyType" => "Customer",
        "InvoiceDate" => "2025-03-03",
        "DueDate" => "2025-03-03",
        "Creator" => "INSTANT_INVOICE_API",
        "CurrRate" => 1,
        "InvoiceType" => "INSTINV",
        "PayTermId" => "0",
        "CreationDate" => "2025-03-03T09:57:35Z",
        "Currency" => "KES",
        "Sent" => "FALSE",
        "MultiCompanyInvoice" => "FALSE",
        "PayTermBaseDate" => "2025-03-03",
        "AdvInv" => false,
        "ProposalExist" => "FALSE",
        "PostPrelTaxWith" => true,
        "PrepayBasedInv" => false,
        "UseProjAddressForTax" => false,
        "SiiProposal" => false,
        "OperationalKey" => "NoValue",
        "OutInvVouDateBase" => "InvoiceDate",
        "OutInvCurrRateBase" => "InvoiceDate",
        "TaxSellCurrRateBase" => "InvoiceDate",
        "AboveTaxControlLimit" => false,
        "EinvoiceSent" => false,
        "UseDeliveryInvAddress" => false,
        "ExcludePostingAuth" => false,
        "CurrDifferenceInvoice" => false,
        "DigitalInvoice" => false,
        "TaxAdjustmentInvoice" => false,
        "Collect" => "FALSE",
        "Cash" => "FALSE",
        "IntAllowed" => "TRUE",
        "AffBaseLedgPost" => "TRUE",
        "TaxCurrRate" => 1,
        "DivFactor" => 1,
        "TaxLiability" => "TAX",
        "InvoiceAddressId" => "1",
        "SupplyCountry" => "KE",
        "DeliveryCountry" => "KE",
        "ParallelCurrRate" => 101.3
    ];
    
    public function index()
    {
        //$client = new IFSClient();
        #$response = $client->get('/CustomersHandling.svc/CustomerInfoSet');
        //$customerService = new CustomerService($client);
        //$invoiceService = new InvoiceService($client,'Instant');
        //$invoiceService = new InvoiceService($client,);
        //$response = $invoiceService->create($this->invoicePayload);
        //$response = $invoiceService->list([],3);
        //dd($response);
        //$salesObjectService = new SalesObjectService();
        //$response = $salesObjectService->getSalesObjects();
        //
        //$currencyInfoService = new CurrencyInfoService();
        //$response = $currencyInfoService->getCurrencyInfo($payload);
        //dd($response);
        //var_dump("Kwisha!");
        $payload = [
            'Company' => "'GBHL'",
            //'Identity' => 'GB-GIL01K',
            'Identity' => "'GB-MOM01U'",
            'TransCurrency' => "'KES'",
            'InvoiceDate' => now()->format('Y-m-d'),
        ];
        /////
        $tariff = Tariff::find(4);
        $billableItem = null;
        foreach($tariff->billableItems as $item){
            //check if specific charge attribute exists
            $item->load('chargeAttributes');
            $attributeCheck  = $item->chargeAttributes->where('name','DefaultTerminalCode')->where('value','BSL-MSA')->first();
            if( !is_null($attributeCheck) ){
                $billableItem = $item;
            }else{
                continue;
            }
        }
    }


    private function _checkBillableItemAttributes($item, $itemAttributes){
        /*
        $attributeList = $item->chargeAttributes;
        $match = $attributeList->where('name',$key)
        foreach($itemAttributes as $key => $value){
            ->where('value',$value)->first();
            if( is_null($match) ){
                throw new \Exception("Billable item missing required attribute: ".$key."=".$value." for item: ".$item->code);
            }
        }
        foreach()
        */
    }
}
