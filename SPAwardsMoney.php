<?php

namespace Modules\Awards\Awards;

use App\Contracts\Award;
use Illuminate\Support\Facades\Log;

class SPAwardsMoney extends Award
{
    public $name = 'SPAwards(Money)';
    
    public $param_description = 'The amount of money a pilot has earned at which to give this award';

    public function check($money = null): bool
    {        
        // Ensure that a valid parameter is provided
        if (is_null($money)) {
            Log::error('SPAwards(Money) | No parameter set.');
            return false;
        }

        // Safely retrieve the user's current money balance
        $currentMoney = optional($this->user->journal->balance->money)->getValue();

        // If currentMoney is null, the user's financial data may be incomplete
        if (is_null($currentMoney)) {
            Log::warning('SPAwards(Money) | User money balance is missing or invalid.');
            return false;
        }

        // Log for debugging
        Log::info("SPAwards(Money) | Pilot (ID: {$this->user->id}) has {$currentMoney}, {$money} needed.");

        // Check if the user's balance exceeds the threshold
        // Returning directly avoids redundant if/else
        return $currentMoney > $money;
    }
}