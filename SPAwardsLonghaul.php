<?php

namespace Modules\Awards\Awards;

use App\Contracts\Award;
use App\Models\Enums\PirepState;
use Illuminate\Support\Facades\Log;

class SPAwardsLonghaul extends Award
{
    public $name = 'SPAwards(Longhaul)';
    
    public $param_description = 'The required number of flights and minimum flight distance (in nm) to give this award, e.g. "10:4000" means 10 flights over 4000 nm.';

    public function check($flightsDistance = null): bool
    {
        // Ensure parameter is provided
        if (is_null($flightsDistance)) {
            Log::error('SPAwards(Longhaul) | No parameter set.');
            return false;
        }

        try {
            // Split into required flight count and minimum distance
            [$requiredFlights, $minDistance] = explode(':', $flightsDistance);
            $requiredFlights = (int) trim($requiredFlights);
            $minDistance = (float) trim($minDistance);
        } catch (\Throwable $e) {
            Log::error("SPAwards(Longhaul) | Invalid format: '{$flightsDistance}'. Expected format: COUNT:DISTANCE");
            return false;
        }

        // Count accepted PIREPs that meet or exceed the minimum distance
        $longHaulFlights = $this->user->pireps()
            ->where('state', PirepState::ACCEPTED) // only count accepted PIREPs
            ->where('distance', '>=', $minDistance)
            ->count();

        // Log for debugging
        Log::info("SPAwards(Longhaul) | Pilot (ID: {$this->user->id}) has {$longHaulFlights} flights > {$minDistance} nm, {$requiredFlights} required.");

        // Return true if pilot has reached or exceeded the goal
        return $longHaulFlights >= $requiredFlights;
    }
}
