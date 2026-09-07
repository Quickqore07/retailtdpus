<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\Ledger;
use Illuminate\Http\Request;
use App\Models\Settings\LedgerDetails;
use App\Models\Settings\CheckMaster;
use App\Models\Settings\Company;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LedgerController extends Controller
{
    public const CODES=[
        'pj'=>['1001.52','1001.50','1001.51','1001.01','1001.53','1001.02','1003.01','1001.04','1001.05','1001.49','1001.54','1001.55']
    ];

    public const FEE_LEDGER_CODES=['1001.52','1001.50','1001.51','1001.01','1001.53','1001.02','1001.55','1001.05','1001.49'];
    public const VISA_MASTER_LEDGER_CODE='1001.01';
    public const CASH_CLEARING_LEDGER_CODE='1003.01';
    public const ACCOUNT_LEDGER_CODE='1001.55';
    public function index(Request $request)
    {
        $this->authorize('access', 'ledger.index');
        $ledgers = Ledger::query()
            ->leftJoin('ledger_details', function ($join) use ($request) {
                $join->on('ledger_details.ledger_id', '=', 'ledgers.id')
                    ->where('ledger_details.company_id', $request->session()->get('company'));
            })
            ->select(
                'ledgers.*',
                'ledger_details.account_no as account_no',
                'ledger_details.code as code',
                'ledger_details.bank_name as bank_name',
                'ledger_details.bank_address as bank_address',
                'ledger_details.starting_check_number as starting_check_number'
            )
            ->filter();
        return to_json([
            'collection' => $ledgers,
        ]);
    }

    public function create()
    {
        $this->authorize('access', 'ledger.create');
        $item = [
            'account_type' => null,
            'code' => null,
            'name' => null,
            'account_no' => null,
            'bank_name' => null,
            'bank_address' => null,
            'transition_code' => null,
            'routing' => null,
        ];
        return to_json([
            'form' => $item,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('access', 'ledger.create');
        $rules = [
            'account_type' => 'required|string|in:General,Bank',
            'code' => 'required|string|max:255',
            'name' => 'required|string|max:255',
        ];
       
        $request->validate($rules);

        try {
            if (LedgerDetails::where('code', $request->code)->where('company_id', $request->session()->get('company'))->exists()) {
                return to_json([
                    'saved' => false,
                    'message' => 'A ledger with this code already exists',
                ], 400);
            }
            $item = Ledger::create($request->only([
                'name', 'account_type',
            ]));

            $compnies= Company::where('id', '!=', $request->session()->get('company'))->get();

            $ledgerDetails = array();
            $ledgerDetails[] = [
                'company_id' => $request->session()->get('company'),
                'ledger_id' => $item->id,
                'code' => $request->code ?? null,
                'account_no' =>  $request->account_no ?? null,
                'bank_name' => $request->bank_name ?? null,
                'bank_address' => $request->bank_address ?? null,
                'transition_code' =>  $request->transition_code ?? null,
                'routing' => $request->routing ?? null,
                'starting_check_number' => $request->starting_check_number ?? null,
                'default_bank' => $request->default_bank ?? false,
            ];
            foreach($compnies as $company){
                $ledgerDetails[] =[
                    'company_id' => $company->id,
                    'ledger_id' => $item->id,
                    'code' => $request->code ?? null,
                    'account_no' =>  null,
                    'bank_name' => $request->bank_name ?? null,
                    'bank_address' => $request->bank_address ?? null,
                    'transition_code' =>  null,
                    'routing' => null,
                    'starting_check_number' => $request->starting_check_number ?? null,
                    'default_bank' => $request->default_bank ?? false,
                ] ;
                if ($request->default_bank) {
                    LedgerDetails::where('ledger_id', $item->id)->where('company_id', $request->session()->get('company'))->update(['default_bank' => false]);
                }
            }
            LedgerDetails::insert($ledgerDetails);
            // if ($item->account_type === 'Bank') {
              

            // }
            return to_json([
                'saved' => true,
                'id' => $item->id,
                'message' => 'Ledger created successfully',
            ]);
        } catch (\Exception $e) {
            dd($e);
            return to_json([
                'saved' => false,
                'message' => 'Ledger creation failed',
            ], 500);
        }
    }

    public function show($id, Request $request)
    {
        $this->authorize('access', 'ledger.show');
        $item = Ledger::with(['ledgerDetails' => function($query) use ($request) {
            $query->where('company_id', $request->session()->get('company'));
        }])->findOrFail($id);


        $item->account_no = is_null($item->ledgerDetails) ? null : $item->ledgerDetails->account_no;
        $item->code = is_null($item->ledgerDetails) ? null : $item->ledgerDetails->code;
        $item->bank_name = is_null($item->ledgerDetails) ? null : $item->ledgerDetails->bank_name;
        $item->bank_address = is_null($item->ledgerDetails) ? null : $item->ledgerDetails->bank_address;
        $item->transition_code = is_null($item->ledgerDetails) ? null : $item->ledgerDetails->transition_code;
        $item->routing = is_null($item->ledgerDetails) ? null : $item->ledgerDetails->routing;
        $item->starting_check_number = is_null($item->ledgerDetails) ? null : $item->ledgerDetails->starting_check_number;
        $item->default_bank = is_null($item->ledgerDetails) ? false : $item->ledgerDetails->default_bank;

        $latestCheck = CheckMaster::where('ledger_id', $item->id)->where('company_id', $request->session()->get('company'))->orderBy('check_number', 'desc')->first();
        $item->latest_check_number = is_null($latestCheck) ? null : $latestCheck->check_number;
        unset($item->ledgerDetails);

        return to_json([
            'model' => $item,
        ]);
    }

    public function edit($id, Request $request)
    {
        $this->authorize('access', 'ledger.update');
        $item = Ledger::with(['ledgerDetails' => function($query) use ($request) {
            $query->where('company_id', $request->session()->get('company'));
        }])->findOrFail($id);
        $item->account_no = is_null($item->ledgerDetails) ? null : $item->ledgerDetails->account_no;
        $item->bank_name = is_null($item->ledgerDetails) ? null : $item->ledgerDetails->bank_name;
        $item->bank_address = is_null($item->ledgerDetails) ? null : $item->ledgerDetails->bank_address;
        $item->transition_code = is_null($item->ledgerDetails) ? null :     $item->ledgerDetails->transition_code;
        $item->routing = is_null($item->ledgerDetails) ? null : $item->ledgerDetails->routing;
        $item->starting_check_number = is_null($item->ledgerDetails) ? null : $item->ledgerDetails->starting_check_number;
        $item->default_bank = is_null($item->ledgerDetails) ? false : $item->ledgerDetails->default_bank;
        $item->code = is_null($item->ledgerDetails) ? null : $item->ledgerDetails->code;
        unset($item->ledgerDetails);

        return to_json([
            'form' => $item,
        ]);
    }

    public function update($id, Request $request)
    {
        $this->authorize('access', 'ledger.update');
        $rules = [
            'account_type' => 'required|string|in:General,Bank',
            'code' => 'required|string|max:255',
            'name' => 'required|string|max:255',
        ];
        if ($request->account_type === 'Bank') {
            $rules['account_no'] = 'nullable|string|max:255';
            $rules['bank_name'] = 'nullable|string|max:255';
            $rules['bank_address'] = 'nullable|string';
            $rules['transition_code'] = 'nullable|string|max:255';
            $rules['routing'] = 'nullable|string|max:255';
            $rules['starting_check_number'] = 'nullable|integer';
        }
        $request->validate($rules);
        DB::beginTransaction();
        try {
            $item = Ledger::findOrFail($id);
            if (LedgerDetails::where('code', $request->code)->where('company_id', $request->session()->get('company'))->where('ledger_id', '!=', $id)->exists()) {
                return to_json([
                    'saved' => false,
                    'message' => 'A ledger with this code already exists',
                ], 400);
            }
            $item->update($request->only([
                 'name', 'account_type',
            ]));

            $ledgerDetails = LedgerDetails::where('ledger_id', $item->id)->where('company_id', $request->session()->get('company'))->first();
            if($ledgerDetails){
                $ledgerDetails->update([
                    'bank_name' => $request->bank_name ?? null,
                    'bank_address' => $request->bank_address ?? null,
                    'code' => $request->code ?? null,
                    'starting_check_number' => $request->starting_check_number ?? null,
                    'default_bank' => $request->default_bank ?? false,
                    'transition_code' => $request->transition_code ?? null,
                    'routing' => $request->routing ?? null,
                    'account_no' => $request->account_no ?? null,
                ]);
            }
            $companies = Company::get();
           
            $ledgerDetailsCompany = LedgerDetails::where('ledger_id', $item->id)->pluck('company_id')->toArray();
            $ledgerDetails=[];
            foreach($companies as $company){
                if(!in_array($company->id, $ledgerDetailsCompany) && $company->id != $request->session()->get('company')){
                    $ledgerDetails[] = [
                        'ledger_id' => $item->id,
                        'company_id' => $company->id,
                        'code' => $request->code ?? null,
                        'bank_name' => $request->bank_name ?? null,
                        'bank_address' => $request->bank_address ?? null,
                        'starting_check_number' => $request->starting_check_number ?? null,
                        'default_bank' => $request->default_bank ?? false,
                        'transition_code' => $request->transition_code ?? null,
                        'routing' => $request->routing ?? null,
                        'account_no' => null,
                    ];
                }else if($company->id == $request->session()->get('company') && !in_array($company->id, $ledgerDetailsCompany)){
                    $ledgerDetails[] = [
                        'ledger_id' => $item->id,
                        'company_id' => $company->id,
                        'code' => $request->code ?? null,
                        'bank_name' => $request->bank_name ?? null,
                        'bank_address' => $request->bank_address ?? null,
                        'starting_check_number' => $request->starting_check_number ?? null,
                        'default_bank' => $request->default_bank ?? false,
                        'transition_code' => $request->transition_code ?? null,
                        'routing' => $request->routing ?? null,
                        'account_no' => $request->account_no ?? null,
                    ];
                }
            }
            LedgerDetails::insert($ledgerDetails);
            DB::commit();

            return to_json([
                'saved' => true,
                'id' => $item->id,
                'message' => 'Ledger updated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            dd($e);
            return to_json([
                'saved' => false,
                'message' => 'Ledger update failed',
            ], 500);
        }
    }

    public function destroy($id, Request $request)
    {
        $this->authorize('access', 'ledger.delete');
        DB::beginTransaction();
        try {
            $item = Ledger::findOrFail($id);

            $checks = CheckMaster::where('ledger_id', $item->id)->get();
            if($checks->count() > 0){
                return to_json([
                    'deleted' => false,
                    'message' => 'Ledger has checks, cannot be deleted',
                ], 400);
            }
            LedgerDetails::where('ledger_id', $item->id)->delete();
            $item->delete();
            DB::commit();
            return to_json([
                'deleted' => true,
                'id' => $item->id,
                'message' => 'Ledger deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            dd($e);
            return to_json([
                'deleted' => false,
                'message' => 'Ledger deletion failed',
            ], 500);
        }
    }
   
    public function getBanks(Request $request)
    {
        $search = request('query');
        $column = request('column') ?? 'ledgers.name';  
        $type = request('type') ?? null;
        $banks = Ledger::where('ledgers.account_type', 'Bank')
            ->leftJoin('ledger_details', function ($join) use ($request) {
                $join->on('ledger_details.ledger_id', '=', 'ledgers.id')
                    ->where('ledger_details.company_id', $request->session()->get('company'));
            })
            ->when($search, function ($query) use ($column, $search) {
                return $query->where($column, 'like', '%' . $search . '%')->orWhere('ledger_details.code', 'like', '%' . $search . '%');
            })
            ->when($type, function ($query) use ($type) {
                if(isset(self::CODES[$type])){
                    return $query->whereIn('ledger_details.code', self::CODES[$type]);
                }
                return $query;
            })
            ->selectRaw('ledgers.id as id, CONCAT(ledger_details.code, " - ",ledgers.name ) as name,ledgers.name as ledger_name,ledger_details.code as code')
            ->get();
            
        return to_json([
            'collection' => $banks
        ]);
    }
    public function search()
    {
        $search = request('query');
        $column = request('column') ?? 'ledgers.name';
        $type = request('type') ?? null;
        $codes = request('codes') ?? null;
        $ledgers = Ledger::join('ledger_details', 'ledger_details.ledger_id', '=', 'ledgers.id')->where('ledger_details.company_id', request()->session()->get('company'))
            ->when($codes, function ($query) use ($codes) {
                return $query->whereIn('ledger_details.code', $codes);
            })->when($search, function ($query) use ($column, $search) {
                return $query->where($column, 'like', '%' . $search . '%')->orWhere('ledger_details.code', 'like', '%' . $search . '%');
            })
            ->when($type, function ($query) use ($type) {
                if(isset(self::CODES[$type])){
                    return $query->whereIn('ledger_details.code', self::CODES[$type]);
                }
                return $query;
            })
            ->selectRaw('ledgers.id as id, CONCAT(ledger_details.code, " - ",ledgers.name ) as name,ledgers.name as ledger_name,ledger_details.code as code')
            ->get();
        return to_json([
            'collection' => $ledgers
        ]);
    }
    public function getdefaultBank($company_id)
    {
        $defaultBank = LedgerDetails::where('company_id', $company_id)->with('ledger')->where('default_bank', true)->first();
        return to_json([
            'bank' => $defaultBank ? $defaultBank->ledger : null
        ]);
    }
}
