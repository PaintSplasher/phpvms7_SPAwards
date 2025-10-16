<?php

namespace Modules\Awards\Awards;

use App\Contracts\Award;
use App\Models\UserAward;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SPAwardsMembership extends Award
{
    public $name = 'SPAwards(Membership)';
    
    public $param_description = 'The days of membership at which to give this award';

    public function check($days = null): bool
    {        
        // Ensure that the number of days parameter is provided
        if (is_null($days)) {
            Log::error('SPAwards(Membership) | No parameter set.');
            return false;
        }

        // Check if the award is already granted
        $award = \App\Models\Award::where('ref_model', get_class($this))
            ->where('ref_model_params', (string) $days)
            ->first();

        if (!$award) {
            Log::error("SPAwards(Membership) | No matching award found.");
            return false;
        }

        $alreadyGranted = UserAward::where('user_id', $this->user->id)
            ->where('award_id', $award->id)
            ->exists();

        if ($alreadyGranted) {
            Log::info("SPAwards(Membership) | Award already granted to Pilot (ID: {$this->user->id}). Skipping...");
            return false;
        }

        // Ensure the user has a valid creation date
        if (is_null($this->user->created_at)) {
            Log::warning('SPAwards(Membership) | User creation date is missing.');
            return false;
        }

        // Calculate how many days the user has been a member
        $membershipDays = $this->user->created_at->diffInDays(Carbon::today());

        // Log for debugging
        Log::info("SPAwards(Membership) | Pilot (ID: {$this->user->id}) has {$membershipDays} days, {$days} days needed.");

        // Check if the membership duration meets or exceeds the required threshold
        return $membershipDays >= $days;
    }
}