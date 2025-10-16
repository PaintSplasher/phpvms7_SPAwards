<?php

namespace Modules\Awards\Awards;

use App\Contracts\Award;
use App\Models\UserAward;
use App\Models\Enums\PirepState;
use Illuminate\Support\Facades\Log;

class SPAwardsExplorer extends Award
{
    public $name = 'SPAwards(Explorer)';
    
    public $param_description = 'The ICAO region prefix and number of flights required, e.g. "ED:100" for 100 flights in Germany.';

    public function check($regionFlights = null): bool
    {
        // Ensure parameter is provided
        if (is_null($regionFlights)) {
            Log::error('SPAwards(Explorer) | No parameter set.');
            return false;
        }

        // Check if the award is already granted
        $award = \App\Models\Award::where('ref_model', get_class($this))
            ->where('ref_model_params', (string) $regionFlights)
            ->first();

        if (!$award) {
            Log::error("SPAwards(Explorer) | No matching award found.");
            return false;
        }

        $alreadyGranted = UserAward::where('user_id', $this->user->id)
            ->where('award_id', $award->id)
            ->exists();

        if ($alreadyGranted) {
            Log::info("SPAwards(Explorer) | Award already granted to Pilot (ID: {$this->user->id}). Skipping...");
            return false;
        }

        try {
            // Split into region prefix and required number of flights
            [$regionPrefix, $requiredFlights] = explode(':', $regionFlights);
            $regionPrefix = strtoupper(trim($regionPrefix));
            $requiredFlights = (int) trim($requiredFlights);
        } catch (\Throwable $e) {
            Log::error("SPAwards(Explorer) | Invalid format: '{$regionFlights}'. Expected format: REGION_PREFIX:COUNT");
            return false;
        }

        // Count accepted flights where either departure or arrival ICAO starts with the region prefix
        $regionalCount = $this->user->pireps()
            ->where('state', PirepState::ACCEPTED) // only count accepted PIREPs
            ->where(function ($query) use ($regionPrefix) {
                $query->where('dpt_airport_id', 'LIKE', "{$regionPrefix}%")
                      ->orWhere('arr_airport_id', 'LIKE', "{$regionPrefix}%");
            })
            ->count();

        // Log for debugging
        Log::info("SPAwards(Explorer) | Pilot (ID: {$this->user->id}) has {$regionalCount} flights in region {$regionPrefix}, {$requiredFlights} required.");

        // Return true if pilot meets or exceeds the requirement
        return $regionalCount >= $requiredFlights;
    }
}
