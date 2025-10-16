<?php

namespace Modules\Awards\Awards;

use App\Contracts\Award;
use App\Models\UserAward;
use App\Models\Enums\PirepState;
use Illuminate\Support\Facades\Log;

class SPAwardsShorthaul extends Award
{
    public $name = 'SPAwards(Shorthaul)';
    
    public $param_description = 'The number of flights and maximum distance (in nm) for the award, e.g. "100:250" means 100 flights under 250 nm.';

    public function check($flightsDistance = null): bool
    {
        // Ensure parameter is provided
        if (is_null($flightsDistance)) {
            Log::error('SPAwards(Shorthaul) | No parameter set.');
            return false;
        }

        // Check if the award is already granted
        $award = \App\Models\Award::where('ref_model', get_class($this))
            ->where('ref_model_params', (string) $flightsDistance)
            ->first();

        if (!$award) {
            Log::error("SPAwards(Shorthaul) | No matching award found.");
            return false;
        }

        $alreadyGranted = UserAward::where('user_id', $this->user->id)
            ->where('award_id', $award->id)
            ->exists();

        if ($alreadyGranted) {
            Log::info("SPAwards(Shorthaul) | Award already granted to Pilot (ID: {$this->user->id}). Skipping...");
            return false;
        }

        try {
            // Split into flight count and max distance
            [$requiredFlights, $maxDistance] = explode(':', $flightsDistance);
            $requiredFlights = (int) trim($requiredFlights);
            $maxDistance = (float) trim($maxDistance);
        } catch (\Throwable $e) {
            Log::error("SPAwards(Shorthaul) | Invalid format: '{$flightsDistance}'. Expected format: COUNT:DISTANCE");
            return false;
        }

        // Count accepted PIREPs under the given distance
        $shortFlights = $this->user->pireps()
            ->where('state', PirepState::ACCEPTED) // only count accepted PIREPs
            ->where('distance', '<=', $maxDistance)
            ->count();

        // Log for debugging
        Log::info("SPAwards(Shorthaul) | Pilot (ID: {$this->user->id}) has {$shortFlights} short-haul flights < {$maxDistance} nm, {$requiredFlights} required.");

        // Check if pilot reached the goal
        return $shortFlights >= $requiredFlights;
    }
}
