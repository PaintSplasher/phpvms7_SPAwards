<?php

namespace Modules\Awards\Awards;

use App\Contracts\Award;
use App\Models\Enums\PirepState;
use Illuminate\Support\Facades\Log;

class SPAwardsFleet extends Award
{
    public $name = 'SPAwards(Fleet)';
    
    public $param_description = 'The number of different aircraft types a pilot must have flown to receive this award';

    public function check($uniqueAircraft = null): bool
    {
        // Ensure parameter is provided and valid
        if (is_null($uniqueAircraft) || !is_numeric($uniqueAircraft)) {
            Log::error('SPAwards(Fleet) | Invalid or missing uniqueAircraft parameter.');
            return false;
        }

        $requiredCount = (int) $uniqueAircraft;

        // Count the number of unique aircraft (ICAO codes) used in accepted PIREPs
        $flownAircraftCount = $this->user->pireps()
            ->where('state', PirepState::ACCEPTED) // only count accepted PIREPs
            ->whereHas('aircraft')
            ->with('aircraft')
            ->get()
            ->pluck('aircraft.icao')
            ->unique()
            ->count();

        // Log for debugging
        Log::info("SPAwards(Fleet) | Pilot (ID: {$this->user->id}) has flown {$flownAircraftCount} unique aircraft types, {$requiredCount} required.");

        // Return true if pilot has met or exceeded the required number of aircraft
        return $flownAircraftCount >= $requiredCount;
    }
}
