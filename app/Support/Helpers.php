<?php

use App\Http\Controllers\Settings\LedgerController;
use Illuminate\Support\Facades\Auth;
use App\Models\Settings\Company;
use App\Models\Settings\EmployeeRoles;
use App\Models\Settings\Ledger;
use Illuminate\Support\Str;
use App\Models\AppNotification;
use App\Models\Onboarding\EmployeeDocument;
use App\Models\Settings\Setting;
use App\Models\Settings\Workgroup;
use App\Models\User;
use App\Services\DefaultSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

function to_json($response, $code = 200)
{
    return response()
        ->json($response, $code);
}

function upload_max_file_size_kb(): int
{
    return (int) config('upload.max_file_size_kb', 10240);
}

function upload_max_file_size_rule(): string
{
    return 'max:' . upload_max_file_size_kb();
}

function generateOTP()
{

    $otp = rand(100000, 999999);

    return $otp;
}
function authorizedCompanies($disableWorkgroupCheck = false )
{
    $user = Auth::user();
    $workgroupId = session('workgroup');
    $checkWorkgroup = !$disableWorkgroupCheck ? false : true;

    // If no authenticated user (e.g., console commands), return all companies
    if (!$user) {
        return Company::when($checkWorkgroup, function ($query) use ($workgroupId) {
            return $query->where('workgroup_id', $workgroupId);
        })->pluck('id')->toArray();
    }

    $user = User::with('role')->find($user->id);
    $company_ids=null;
    if($checkWorkgroup){
        $company_ids = Company::authorizedWorkgroup()->pluck('id')->toArray();
    }
    // Check if user has a role before accessing it
    if (!$user->role) {
        return [];
    }
    if(strtolower($user->role->name) == 'admin'){
        return $checkWorkgroup ? Company::authorizedWorkgroup()->pluck('id')->toArray() : Company::pluck('id')->toArray();
    }
    else if($user->companies && count($user->companies_array) > 0){
        return $company_ids ? array_intersect($user->companies_array, $company_ids) : $user->companies_array;
    }
    else if($user->role->companies && count($user->role->companies_array) > 0){
        return $company_ids ? array_intersect($user->role->companies_array, $company_ids) : $user->role->companies_array;
    }
    else{
        return [];
    }
}
function rolesByCode()
{
    $roles = EmployeeRoles::with('subRoles')->authorizedWorkgroup()->get();
    $rolesByCode = [];
    foreach ($roles as $role) {
        $rolesByCode[$role->code] = $role->id;
        foreach ($role->subRoles as $subRole) {
            $rolesByCode[$subRole->code] = $role->id;
        }
    }
    return $rolesByCode;
}


function numberTowords($num)
{
    $ones = array(
        0 => "ZERO",
        1 => "ONE",
        2 => "TWO",
        3 => "THREE",
        4 => "FOUR",
        5 => "FIVE",
        6 => "SIX",
        7 => "SEVEN",
        8 => "EIGHT",
        9 => "NINE",
        10 => "TEN",
        11 => "ELEVEN",
        12 => "TWELVE",
        13 => "THIRTEEN",
        14 => "FOURTEEN",
        15 => "FIFTEEN",
        16 => "SIXTEEN",
        17 => "SEVENTEEN",
        18 => "EIGHTEEN",
        19 => "NINETEEN"
    );

    $tens = array(
        0 => "ZERO",
        1 => "TEN",
        2 => "TWENTY",
        3 => "THIRTY",
        4 => "FORTY",
        5 => "FIFTY",
        6 => "SIXTY",
        7 => "SEVENTY",
        8 => "EIGHTY",
        9 => "NINETY"
    );

    $hundreds = array(
        "HUNDRED",
        "THOUSAND",
        "MILLION",
        "BILLION",
        "TRILLION",
        "QUARDRILLION"
    );

    $num = number_format($num, 2, ".", ",");
    $num_arr = explode(".", $num);
    $wholenum = $num_arr[0];
    $decnum = $num_arr[1];
    $whole_arr = array_reverse(explode(",", $wholenum));
    krsort($whole_arr, 1);
    $rettxt = "";
    foreach ($whole_arr as $key => $i) {
        while (substr($i, 0, 1) == "0")
            $i = substr($i, 1, 5);
        if ($i < 20) {
            if (array_key_exists($i, $ones)) {
                $rettxt .= $ones[$i];
            }
        } elseif ($i < 100) {
            if (substr($i, 0, 1) != "0")
                $rettxt .= $tens[substr($i, 0, 1)];
            if (substr($i, 1, 1) != "0")
                $rettxt .= " " . $ones[substr($i, 1, 1)];
        } else {
            if (substr($i, 0, 1) != "0")
                $rettxt .= $ones[substr($i, 0, 1)] . " " . $hundreds[0];
            if (substr($i, 1, 2) < 20 && substr($i, 1, 2) >= 10) {
                $rettxt .= " " . $ones[substr($i, 1, 2)];
            } else {
                if (substr($i, 1, 1) != "0")
                    $rettxt .= " " . $tens[substr($i, 1, 1)];
                if (substr($i, 2, 1) != "0")
                    $rettxt .= " " . $ones[substr($i, 2, 1)];
            }
        }
        if ($key > 0) {
            $rettxt .= " " . $hundreds[$key] . " ";
        }
    }
    // if ($decnum > 0) {
    $rettxt .= " and ";
    // if ($decnum < 20) {
    //     $rettxt .= $ones[$decnum];
    // } elseif ($decnum < 100) {
    //     $rettxt .= $tens[substr($decnum, 0, 1)];
    //     $rettxt .= " " . $ones[substr($decnum, 1, 1)];
    // }

    $rettxt .= $decnum . "/100";
    // }

    $rettxt .= " Dollars";
    return $rettxt;
}

function mysql2dmy($input)
{
    $output = false;
    $input1 = $input;
    $input = substr($input, 0, 10);
    $d = explode('-', $input);
    if (is_array($d) && count($d) >= 3) {
        if (checkdate($d[1], $d[2], $d[0]) || ($d[2] == "00" && $d[1] == "00")) {
            $output = "$d[1]/$d[2]/$d[0]";
        }
        if (substr($input1, 11))
            $output .= " " . substr($input1, 11);
    }
    return $output;
}

 function generateMicr(string $micrText): string
    {
        $width = 1200;
        $height = 120;

        $img = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        imagefill($img, 0, 0, $white);

        $font = public_path('fonts/MicrEncoding-ZEDJ.ttf');

        imagettftext(
            $img,
            36,       // font size
            0,        // angle
            20,       // x
            80,       // y
            $black,
            $font,
            $micrText
        );

        $path = storage_path('app/micr');
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $file = $path.'/'.md5($micrText).'.png';
        imagepng($img, $file);

        return $file;
    }


function getLedgersByCode($type, $company_id)
{
    $codes = LedgerController::CODES[$type];
    $ledgers = Ledger::join('ledger_details', 'ledger_details.ledger_id', '=', 'ledgers.id')->where('ledger_details.company_id', $company_id)->whereIn('ledger_details.code', $codes)->selectRaw('ledgers.id as id, concat(ledger_details.code, " - ",ledgers.name) as name, ledger_details.code as code,ledgers.name as ledger_name')->get();
    return $ledgers;
}

function custom_encrypt($string)
{
    $key = hash('sha256', config('crypto.custom_secret_key'));
    $iv = Str::random(16); // 16 bytes IV for AES-256-CBC
    $iv_hex = bin2hex($iv); // Convert to hexadecimal for storage

    $encrypted = openssl_encrypt($string, config('crypto.custom_cipher'), $key, 0, $iv);

    return base64_encode($iv . '::' . $encrypted);
}

function custom_decrypt($string)
{
    if (empty($string)) {
        return null;
    }
    $key = hash('sha256', config('crypto.custom_secret_key'));
    $decoded = base64_decode($string, true);
    if ($decoded === false || strlen($decoded) < 19) {
        return null;
    }
    $iv = substr($decoded, 0, 16);
    $encrypted = substr($decoded, 18); // 16 bytes IV + '::'

    $decrypted = openssl_decrypt($encrypted, config('crypto.custom_cipher'), $key, 0, $iv);

    return $decrypted !== false ? $decrypted : null;
}

function uploadDocument($name, $path, $employeeId, $title = null, $documentType = null)
{
    $document = EmployeeDocument::where('employee_id', $employeeId)->where('document_name', 'like', '%' . $name . '%')->where('document_type', $documentType)->first();
    if($document) {
        $document->document_path = $path;
        $document->document_name = $title ?? $name;
        if ($documentType !== null) {
            $document->document_type = $documentType;
        }
        $document->save();
    }
    else {
        $data = [
            'employee_id' => $employeeId,
            'document_name' => $title ?? $name,
            'document_path' => $path,
        ];
        if ($documentType !== null) {
            $data['document_type'] = $documentType;
        }
        EmployeeDocument::create($data);
    }
}

function isDCWorkgroup()
{
    $workgroup = Workgroup::find(session('workgroup'));
    if($workgroup && ($workgroup->name == 'DC' || $workgroup->name == 'DE')) {
        return true;
    } else {
        return false;
    }
}

function isPAWorkgroup()
{
    $workgroup = Workgroup::find(session('workgroup'));
    return $workgroup && $workgroup->name === 'PA';
}


/**
 * Store a single in-app notification (app_notifications).
 */
function store_app_notification(?int $userId, string $content, string $type = 'info'): ?AppNotification
{
    if (!$userId) {
        return null;
    }

    return AppNotification::query()->create([
        'user_id' => $userId,
        'content' => $content,
        'type' => $type,
    ]);
}

/**
 * Store the same in-app notification for many users (one row per user).
 */
function store_app_notifications_for_users(array $userIds, string $content, string $type = 'info'): int
{
    $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
    if ($userIds === []) {
        return 0;
    }

    $now = now();
    $rows = [];
    foreach ($userIds as $id) {
        $rows[] = [
            'user_id' => $id,
            'content' => $content,
            'type' => $type,
            'created_at' => $now,
        ];
    }

    AppNotification::insert($rows);

    return count($rows);
}


function getSettingValue($key, $default = null)
{
    $setting = Setting::where('key', $key)->first();
    if($setting && is_numeric($default) && is_numeric($setting->value) && $setting->value > 0){
        return $setting->value;
    }
    if($setting && $setting->value != null){
        return $setting->value;
    }
    
    $settings = DefaultSettings::schema();
    foreach($settings as $setting){
        if($setting['key'] == $key && !$setting){
            return $setting['default_value'];
        }
    }
    return $default;
}

function generateUniqueRandomNumber($table, $column, $length = 10)
{
    do {
        $number = generateRandomString($length);
    } while (DB::table($table)->where($column, $number)->exists());

    return $number;
}

function generateRandomString($length = 10)
{
    $characters = '0123456789';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}


function calculateCurrentPayrollEndDate($date)
{
    $isDC = isDCWorkgroup();
    $carbonDate = Carbon::parse($date, 'America/New_York');
    $yearStart = Carbon::parse($carbonDate->year . '-01-01');
    $dayOfWeek = (int) $yearStart->format('N');
    $daysToMonday = $isDC ? ($dayOfWeek === 7 ? 0 : 1 - $dayOfWeek) : ($dayOfWeek === 7 ? -6 : 1 - $dayOfWeek - 7);
    $firstMonday = $yearStart->copy()->addDays($daysToMonday);
    $diffDays = $firstMonday->diffInDays($carbonDate, false);
    $periodIndex = floor($diffDays / 14);
    $eow = $firstMonday->copy()->addDays(($periodIndex * 14) + 13);
    return $eow->format('Y-m-d', 'America/New_York');
}
