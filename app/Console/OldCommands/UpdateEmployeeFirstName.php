<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Payroll\EmployeeHours;
use App\Models\Payroll\EmployeeRates;
use App\Models\Role;
use App\Models\Settings\Company;
use App\Models\Settings\EmployeeRoles;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateEmployeeFirstName extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:update-employee';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add employee to the database';

    /**
     * Execute the console command.
     */
    protected $companyMap = [
        '163'=>70,
        '164'=>71,
        '165'=>72,
        '166'=>73,
        '167'=>74,
        '168'=>75,
        '169'=>76,
        '133'=>77,
        '170'=>78,
        '134'=>79,
        '135'=>80,
        '171'=>81,
        '138'=>82,
        '172'=>83,
        '186'=>84,
        '173'=>85,
        '174'=>86,
        '175'=>87,
        '176'=>88,
        '177'=>89,
        '187'=>90,
        '188'=>91,
        '178'=>92,
        '179'=>93,
        '180'=>94,
        '189'=>95,
        '181'=>96,
        '190'=>97,
        '182'=>98,
        '102'=>99,
        '183'=>100,
        '184'=>101,
        '185'=>102,
        '191'=>103,
        '192'=>104,
        '193'=>105,
        '194'=>106,
        '136'=>107,
        '139'=>108,
        '195'=>109,
        '196'=>110,
        '197'=>111,
        '198'=>112,
        '137'=>113,
        '103'=>114,
        '104'=>115,
        '105'=>116,
        '106'=>117,
        '107'=>118,
        '108'=>119,
        '109'=>120,
        '110'=>121,
        '111'=>122,
        '112'=>123,
        '113'=>124,
        '140'=>125,
        '141'=>126,
        '142'=>127,
        '114'=>128,
        '115'=>129,
        '116'=>130,
        '117'=>131,
        '118'=>132,
        '119'=>133,
        '120'=>134,
        '121'=>135,
        '143'=>136,
        '122'=>137,
        '123'=>138,
        '124'=>139,
        '125'=>140,
        '126'=>141,
        '144'=>142,
        '127'=>143,
        '145'=>144,
        '128'=>145,
        '129'=>146,
        '130'=>147,
        '146'=>148,
        '147'=>149,
        '131'=>150,
        '132'=>151,
        '148'=>152,
        '149'=>153,
        '150'=>154,
        '151'=>155,
        '152'=>156,
        '153'=>157,
        '154'=>158,
        '155'=>159,
        '156'=>160,
        '157'=>161,
        '158'=>162,
        '159'=>163,
        '160'=>164,
        '161'=>165,
        '162'=>166,  
    ];

    protected $employeeRoles=[
        '5'=>33,
        '6'=>34,
        '7'=>35,
        '8'=>36,
        '9'=>37,
        '10'=>38,
        '11'=>39,
        '12'=>40,
        '13'=>41,
        '14'=>42,
        '15'=>43,
        '16'=>44,
        '17'=>45,
        '18'=>46,
        '19'=>47,
    ];

    public function handle()
    {
        DB::beginTransaction();
        try {
            $data = DB::table('onboarding_list_2 as ol')
            ->leftJoin('onboarding_i9_data as i9', 'i9.onboarding_number', '=', 'ol.onboarding_number')
            ->select(
                'ol.onboarding_number as onboarding_number',
                'ol.employeeId as employee_id',
                'ol.id as id',
                'i9.state as state',
                'i9.review_and_verification_documents as review_and_verification_documents',
                'i9.first_name as first_name',
                'i9.last_name as last_name',
                'i9.middle_name as middle_name',
                'i9.address as address',
                'i9.city as city',
                'i9.zip_code as zip_code',
                'i9.emp_telephone_number as emp_telephone_number',
                'i9.emp_emal_address as emp_emal_address',
            )
            ->groupBy('onboarding_number', 'employee_id', 'id', 'state', 'review_and_verification_documents', 'first_name', 'last_name', 'middle_name', 'address', 'city', 'zip_code', 'emp_telephone_number', 'emp_emal_address')
            ->get();
            
            $successCount = 0;
            foreach($data as $item){
                if(!$item->first_name && !$item->last_name && !$item->middle_name){
                    continue;
                }
                $employee = Employee::where('employee_id', $item->employee_id)->where('workgroup_id', 2)->where('pos_name', 'like', '%'.$item->first_name.'%')->first();
                if(!$employee){
                    $this->error('Employee not found: '.$item->employee_id .' '.$item->first_name);
                    continue;
                }
                $employee->first_name = $item->first_name;
                $employee->last_name = $item->last_name;
                $employee->middle_name = $item->middle_name;
                if(!$employee->address){
                    $employee->street = $item->address;
                }
                if(!$employee->city){
                    $employee->city = $item->city;
                }
                if(!$employee->state){
                    $employee->state = $item->state;
                }
                if(!$employee->zip_code){
                    $employee->zip = $item->zip_code;
                }
                if(!$employee->emp_telephone_number){
                    $employee->phone = $item->emp_telephone_number;
                }
                if(!$employee->email){
                    $employee->email = $item->emp_emal_address;
                }
                $employee->save();
                $this->info('Employee updated: '.$item->employee_id .' '.$item->first_name);
                $successCount++;
            }

            DB::commit();

            $this->info('Employee updated: '.$successCount);
        }catch(\Throwable $th){
            info($th);
            $this->error($th->getMessage());
            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }

    function normalizeName($name) {
        $name = strtoupper(trim($name));
        $parts = preg_split('/\s+/', $name);
    
        if (count($parts) >= 2) {
            return $parts[0] . ' ' . end($parts); // FIRST + LAST
        }
    
        return $name;
    }
}


