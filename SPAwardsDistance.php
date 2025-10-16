<?php

namespace Modules\Awards\Awards;

use App\Contracts\Award;
use App\Models\UserAward;
use App\Models\Enums\PirepState;
use Illuminate\Support\Facades\Log;

class SPAwardsDistance extends Award
{
    public $name = 'SPAwards(Distance)';
    
    public $param_description = 'The total distance flown in nautical miles at which to give this award';

    public function check($distance = null): bool
    {
        // Ensure parameter is provided and valid
        if (is_null($distance) || !is_numeric($distance)) {
            Log::error('SPAwards(Distance) | Invalid or missing distance parameter.');
            return false;
        }

        // Check if the award is already granted
        $award = \App\Models\Award::where('ref_model', get_class($this))
            ->where('ref_model_params', (string) $distance)
            ->first();

        if (!$award) {
            Log::error("SPAwards(Distance) | No matching award found.");
            return false;
        }

        $alreadyGranted = UserAward::where('user_id', $this->user->id)
            ->where('award_id', $award->id)
            ->exists();

        if ($alreadyGranted) {
            Log::info("SPAwards(Distance) | Award already granted to Pilot (ID: {$this->user->id}). Skipping...");
            return false;
        }

        // Convert parameter to float
        $requiredDistance = (float) $distance;

        // Sum up all accepted PIREP distances for the user
        $totalDistance = $this->user->pireps()
            ->where('state', PirepState::ACCEPTED) // only count accepted PIREPs
            ->sum('distance');

        // Log for debugging
        Log::info("SPAwards(Distance) | Pilot (ID: {$this->user->id}) has flown {$totalDistance} nm, {$requiredDistance} nm needed.");

        // Check if the pilot meets or exceeds the required total distance
        return $totalDistance >= $requiredDistance;
    }
}
