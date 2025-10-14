<?php

namespace Modules\Awards\Awards;

use App\Contracts\Award;
use App\Models\Enums\PirepState;
use Illuminate\Support\Facades\Log;

class SPAwardsAircraft extends Award
{
    public $name = 'SPAwards(Aircraft)';
    
    public $param_description = 'The ICAO code of the aircraft and the number of flights flown to give this award, e.g. "A321:25"';

    public function check($icaoFlights = null): bool
    {
        // Ensure parameter is provided and valid
        if (is_null($icaoFlights)) {
            Log::error('SPAwards(Aircraft) | No parameter set.');
            return false;
        }

        try {
            // Split parameter into aircraft ICAO code and required number of flights
            [$icao, $requiredFlights] = explode(':', $icaoFlights);
            $icao = strtoupper(trim($icao));
            $requiredFlights = (int) trim($requiredFlights);
        } catch (\Throwable $e) {
            Log::error("SPAwards(Aircraft) | Invalid format: '{$icaoFlights}'. Expected format: ICAO:COUNT");
            return false;
        }

        // Count the number of accepted PIREPs with this aircraft type
        $flownCount = $this->user->pireps()
            ->whereHas('aircraft', function ($query) use ($icao) {
                $query->where('icao', $icao);
            })
            ->where('state', PirepState::ACCEPTED) // only count accepted PIREPs
            ->count();

        // Log for debugging
        Log::info("SPAwards(Aircraft) | Pilot (ID: {$this->user->id}) has {$flownCount} flights with aircraft {$icao}, {$requiredFlights} needed.");

        // Return true if the pilot meets or exceeds the required flights
        return $flownCount >= $requiredFlights;
    }
}
