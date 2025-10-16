<?php

namespace Modules\Awards\Awards;

use App\Contracts\Award;
use App\Models\UserAward;
use App\Models\Enums\PirepState;
use Illuminate\Support\Facades\Log;

class SPAwardsFuelburner extends Award
{
    public $name = 'SPAwards(Fuelburner)';
    
    public $param_description = 'Award for flights that used more than the specified fuel amount (in kg)';

    public function check($minFuelKg = null): bool
    {
        // Ensure parameter is provided and valid
        if (is_null($minFuelKg) || !is_numeric($minFuelKg)) {
            Log::error('SPAwards(Fuelburner) | Invalid or missing minFuelKg parameter.');
            return false;
        }

        // Check if the award is already granted
        $award = \App\Models\Award::where('ref_model', get_class($this))
            ->where('ref_model_params', (string) $minFuelKg)
            ->first();

        if (!$award) {
            Log::error("SPAwards(Fuelburner) | No matching award found.");
            return false;
        }

        $alreadyGranted = UserAward::where('user_id', $this->user->id)
            ->where('award_id', $award->id)
            ->exists();

        if ($alreadyGranted) {
            Log::info("SPAwards(Fuelburner) | Award already granted to Pilot (ID: {$this->user->id}). Skipping...");
            return false;
        }

        $minFuelKg = (float) $minFuelKg;

        // Check accepted flights with fuel used over the threshold
        $qualifyingFlight = $this->user->pireps()
            ->where('state', PirepState::ACCEPTED) // only count accepted PIREPs
            ->where('fuel_used', '>', $minFuelKg)
            ->exists();

        // Log for debugging
        Log::info("SPAwards(Fuelburner) | Pilot (ID: {$this->user->id}) has " . ($qualifyingFlight ? "at least one flight" : "no flights") . " exceeding {$minFuelKg} kg fuel used.");

        return $qualifyingFlight;
    }
}
