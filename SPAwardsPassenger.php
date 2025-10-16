<?php

namespace Modules\Awards\Awards;

use App\Contracts\Award;
use App\Models\UserAward;
use App\Models\Enums\PirepState;
use Illuminate\Support\Facades\Log;

class SPAwardsPassenger extends Award
{
    public $name = 'SPAwards(Passenger)';
    public $param_description = 'The airline ICAO, aircraft ICAO, and total number of passengers required, e.g. "DLH:A320:10000".';

    public function check($params = null): bool
    {
        // Ensure parameter is provided and valid
        if (is_null($params)) {
            Log::error('SPAwards(Passenger) | No parameter set.');
            return false;
        }

        // Check if the award is already granted
        $award = \App\Models\Award::where('ref_model', get_class($this))
            ->where('ref_model_params', (string) $params)
            ->first();

        if (!$award) {
            Log::error("SPAwards(Passenger) | No matching award found.");
            return false;
        }

        $alreadyGranted = UserAward::where('user_id', $this->user->id)
            ->where('award_id', $award->id)
            ->exists();

        if ($alreadyGranted) {
            Log::info("SPAwards(Passenger) | Award already granted to Pilot (ID: {$this->user->id}). Skipping...");
            return false;
        }

        try {
            [$airlineIcao, $aircraftIcao, $requiredPax] = explode(':', $params);
            $airlineIcao  = strtoupper(trim($airlineIcao));
            $aircraftIcao = strtoupper(trim($aircraftIcao));
            $requiredPax  = (int) trim($requiredPax);
        } catch (\Throwable $e) {
            Log::error("SPAwards(Passenger) | Invalid format: '{$params}'. Expected AIRLINE:AIRCRAFT:PAX_COUNT");
            return false;
        }

        $pireps = $this->user->pireps()
            ->where('state', PirepState::ACCEPTED) // only count accepted PIREPs
            ->whereHas('airline', fn($q) => $q->where('icao', $airlineIcao))
            ->whereHas('aircraft', fn($q) => $q->where('icao', $aircraftIcao))
            ->with('fares')
            ->get();

        // Sum all fares with type = 0 (passengers)
        $totalPax = $pireps->flatMap->fares
            ->filter(fn($fare) => (int)$fare->type === 0)
            ->sum('count');

        // Log for debugging
        Log::info("SPAwards(Passenger) | Pilot (ID: {$this->user->id}) carried {$totalPax} pax with {$airlineIcao}/{$aircraftIcao}, {$requiredPax} required.");

        return $totalPax >= $requiredPax;
    }
}
