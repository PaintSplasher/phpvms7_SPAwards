<?php

namespace Modules\Awards\Awards;

use App\Contracts\Award;
use App\Models\Enums\PirepState;
use Illuminate\Support\Facades\Log;

class SPAwardsFuelburner extends Award
{
    public $name = 'SPAwards(Fuelburner)';
    
    public $param_description = 'Award for flights that used more than the specified fuel amount (in kg)';

    public function check($minFuelKg = null): bool
    {
        // Validate parameter
        if (is_null($minFuelKg) || !is_numeric($minFuelKg)) {
            Log::error('SPAwards(Fuelburner) | Invalid or missing minFuelKg parameter.');
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
