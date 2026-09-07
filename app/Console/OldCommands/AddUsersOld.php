<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\Settings\EmployeeRoles;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddUsersOld extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:users---old';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add users to the database';

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

    protected $roleMap=[
        1	=>'superadmin',
        2	=>'Data Entry',
        5	=>'Admin',
        6	=>'Pre Patel',
        7	=>'General Manager',
        8	=>'Area Manager',
        9	=>'Regional Manager',
        10	=>'Director Operation',
        11	=>'Vice President',
        12	=>'Human Resource',
        13	=>'Payroll Specialist',
        16	=>'Maintenance',
        17	=>'Admin Staff',
        18	=>'hr',
    ];
    public function handle()
    {
        DB::beginTransaction();
        try {
        
            $roles =[
                ['name' => 'superadmin','workgroup_id' => 2], 
                ['name' => 'Data Entry','workgroup_id' => 2], 
                ['name' => 'Admin','workgroup_id' => 2], 
                ['name' => 'Pre Patel','workgroup_id' => 2], 
                ['name' => 'General Manager','workgroup_id' => 2], 
                ['name' => 'Area Manager','workgroup_id' => 2], 
                ['name' => 'Regional Manager','workgroup_id' => 2], 
                ['name' => 'Director Operation','workgroup_id' => 2], 
                ['name' => 'Vice President','workgroup_id' => 2], 
                ['name' => 'Human Resource','workgroup_id' => 2], 
                ['name' => 'Payroll Specialist','workgroup_id' => 2], 
                ['name' => 'Maintenance','workgroup_id' => 2], 
                ['name' => 'Admin Staff','workgroup_id' => 2], 
                ['name' => 'hr','workgroup_id' => 2], 
            ];
            Role::where('workgroup_id', 2)->delete();
            Role::insert($roles);
            
            $this->info('Roles added successfully');

            $employeeRoles =[
                ['code'=>'','name' => 'Crew Member','workgroup_id' => 2],
                ['code'=>'M3','name' => 'Shift Leader', 'workgroup_id' => 2], 
                ['code'=>'MG','name' => 'General Manager', 'workgroup_id' => 2],
                ['code'=>'M6','name' => 'Area Manager', 'workgroup_id' => 2],
                ['code'=>'','name' => 'Regional Manager', 'workgroup_id' => 2],
                ['code'=>'','name' => 'Director Operation', 'workgroup_id' => 2],
                ['code'=>'','name' => 'VP Operation', 'workgroup_id' => 2],
                ['code'=>'M4','name' => 'Salaried Asst Manager', 'workgroup_id' => 2],
                ['code'=>'IN','name' => 'Rest Team Member', 'workgroup_id' => 2],
                ['code'=>'DV','name' => 'Driver', 'workgroup_id' => 2],
                ['code'=>'DR','name' => 'Driver on Rd', 'workgroup_id' => 2],
                ['code'=>'M5','name' => 'Manager Designate', 'workgroup_id' => 2],   
                ['code'=>'TR30','name' => 'Training General Manager', 'workgroup_id' => 2],
                ['code'=>'DR50','name' => 'Fleet Car Delivery Driver', 'workgroup_id' => 2],    
                ['code'=>'DR51','name' => 'Fleet Car Delivery Driver', 'workgroup_id' => 2],
            ];
            EmployeeRoles::where('workgroup_id', 2)->delete();
            EmployeeRoles::insert($employeeRoles);
            $this->info('Employee roles added successfully');

            $allusers = DB::table('admins')->get();
            $newRoles = Role::whereIn('name', array_values($this->roleMap))->pluck('id','name');
            info($newRoles);
            

            $newUsers = [];

            foreach($allusers as $user){
                $roleName = $this->roleMap[$user->role_id];
                $companyId = json_decode($user->company_id, true);

                $companyId = explode(',', $companyId[0]);
                $companyIds = array_filter(array_map(function($item){
                    if(isset($this->companyMap[$item])){
                        return $this->companyMap[$item];
                    }
                    return null;
                }, $companyId));
                $companyIds = array_unique($companyIds);
                $newUsers[] = [
                    'name' => $user->name ?? "",
                    'username' => $user->username,
                    'email' => $user->email ?? "",
                    'phone' => $user->phone ?? "",
                    'role_id' => $newRoles[$roleName],
                    'workgroup_id' => 2,
                    'companies' => implode(',', $companyIds),
                    'password' => bcrypt($user->view_password),
                    'current_password' => $user->view_password,
                ];
            }
            User::where('workgroup_id', 2)->delete();
            User::insert($newUsers);
            DB::commit();
            $this->info('Users added successfully');
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->error($th->getMessage());
            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }
}
