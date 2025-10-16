<?php

namespace Modules\Awards\Awards;

use App\Contracts\Award;
use App\Models\UserAward;
use App\Models\Enums\PirepState;
use Illuminate\Support\Facades\Log;

class SPAwardsAirline extends Award
{
    public $name = 'SPAwards(Airline)';
    
    public $param_description = 'The ICAO code of the airline and the number of flights flown to give this award, e.g. "GEC:25"';

    public function check($icaoFlights = null): bool
    {
        // Ensure parameter is provided and valid
        if (is_null($icaoFlights)) {
            Log::error('SPAwards(Airline) | No parameter set.');
            return false;
        }

        // Check if the award is already granted
        $award = \App\Models\Award::where('ref_model', get_class($this))
            ->where('ref_model_params', (string) $icaoFlights)
            ->first();

        if (!$award) {
            Log::error("SPAwards(Airline) | No matching award found.");
            return false;
        }

        $alreadyGranted = UserAward::where('user_id', $this->user->id)
            ->where('award_id', $award->id)
            ->exists();

        if ($alreadyGranted) {
            Log::info("SPAwards(Airline) | Award already granted to Pilot (ID: {$this->user->id}). Skipping...");
            return false;
        }

        try {
            // Split the parameter into ICAO and required flight count
            [$icao, $requiredFlights] = explode(':', $icaoFlights);
            $icao = trim($icao);
            $requiredFlights = (int) trim($requiredFlights);
        } catch (\Throwable $e) {
            Log::error("SPAwards(Airline) | Invalid format: '{$icaoFlights}'. Expected format: ICAO:COUNT");
            return false;
        }

        // Get number of accepted PIREPs by the user for the given airline
        $flownCount = $this->user->pireps()
            ->whereHas('airline', function ($query) use ($icao) {
                $query->where('icao', strtoupper($icao));
            })
            ->where('state', PirepState::ACCEPTED) // only count accepted PIREPs
            ->count();

        // Log for debugging
        Log::info("SPAwards(Airline) | Pilot (ID: {$this->user->id}) has {$flownCount} flights with {$icao}, {$requiredFlights} needed.");

        // Return true if requirement met or exceeded
        return $flownCount >= $requiredFlights;
    }
}
