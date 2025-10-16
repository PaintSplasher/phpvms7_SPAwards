<?php

namespace Modules\Awards\Awards;

use App\Contracts\Award;
use App\Models\UserAward;
use App\Models\Enums\PirepState;
use Illuminate\Support\Facades\Log;

class SPAwardsLoyalty extends Award
{
    public $name = 'SPAwards(Loyalty)';
    
    public $param_description = 'The ICAO code of the hub airport and the number of flights from/to it required for this award, e.g. "EDDF:50"';

    public function check($hubFlights = null): bool
    {
        // Ensure parameter is provided
        if (is_null($hubFlights)) {
            Log::error('SPAwards(Loyalty) | No parameter set.');
            return false;
        }

        // Check if the award is already granted
        $award = \App\Models\Award::where('ref_model', get_class($this))
            ->where('ref_model_params', (string) $hubFlights)
            ->first();

        if (!$award) {
            Log::error("SPAwards(Loyalty) | No matching award found.");
            return false;
        }

        $alreadyGranted = UserAward::where('user_id', $this->user->id)
            ->where('award_id', $award->id)
            ->exists();

        if ($alreadyGranted) {
            Log::info("SPAwards(Loyalty) | Award already granted to Pilot (ID: {$this->user->id}). Skipping...");
            return false;
        }

        try {
            // Split parameter into hub ICAO and required flights
            [$hubIcao, $requiredFlights] = explode(':', $hubFlights);
            $hubIcao = strtoupper(trim($hubIcao));
            $requiredFlights = (int) trim($requiredFlights);
        } catch (\Throwable $e) {
            Log::error("SPAwards(Loyalty) | Invalid format: '{$hubFlights}'. Expected format: ICAO:COUNT");
            return false;
        }

        // Count accepted PIREPs where the departure or arrival airport matches the hub ICAO
        $hubFlightCount = $this->user->pireps()
            ->where('state', PirepState::ACCEPTED) // only count accepted PIREPs
            ->where(function ($query) use ($hubIcao) {
                $query->where('dpt_airport_id', $hubIcao)
                      ->orWhere('arr_airport_id', $hubIcao);
            })
            ->count();

        // Log for debugging
        Log::info("SPAwards(Loyalty) | Pilot (ID: {$this->user->id}) has {$hubFlightCount} flights from/to {$hubIcao}, {$requiredFlights} required.");

        // Return true if pilot meets or exceeds the requirement
        return $hubFlightCount >= $requiredFlights;
    }
}
