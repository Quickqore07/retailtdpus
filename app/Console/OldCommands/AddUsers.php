<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\Settings\EmployeeRoles;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:users';

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

    public function handle()
    {
        DB::beginTransaction();
        try {
        
           

            $allusers = DB::table('admins')->get()->keyBy('username');
            $currentUsers = User::where('workgroup_id', 2)->get()->keyBy('username');


            foreach($allusers as $user){
                $currentUser = $currentUsers[$user->username] ?? null;
                if(!$currentUser){
                    continue;
                }
                $companyIds = json_decode($user->company_id, true);
                $companyIds = array_filter(array_map(function($item){
                    if(isset($this->companyMap[$item])){
                        return $this->companyMap[$item];
                    }
                    return null;
                }, $companyIds));
                $companyIds = array_unique($companyIds);
                $currentUser->companies = implode(',', $companyIds);
                $currentUser->updated_by = 1;
                $currentUser->save();
            }
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
