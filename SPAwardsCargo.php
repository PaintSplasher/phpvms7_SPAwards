<?php

namespace Modules\Awards\Awards;

use App\Contracts\Award;
use App\Models\Enums\PirepState;
use Illuminate\Support\Facades\Log;

class SPAwardsCargo extends Award
{
    public $name = 'SPAwards(Cargo)';
    public $param_description = 'The airline ICAO, aircraft ICAO, and total cargo count required, e.g. "GEC:B744:500000".';

    public function check($params = null): bool
    {
        // Ensure parameter is provided and valid
        if (is_null($params)) {
            Log::error('SPAwards(Cargo) | No parameter set.');
            return false;
        }

        try {
            [$airlineIcao, $aircraftIcao, $requiredCargo] = explode(':', $params);
            $airlineIcao  = strtoupper(trim($airlineIcao));
            $aircraftIcao = strtoupper(trim($aircraftIcao));
            $requiredCargo = (float) trim($requiredCargo);
        } catch (\Throwable $e) {
            Log::error("SPAwards(Cargo) | Invalid format: '{$params}'. Expected AIRLINE:AIRCRAFT:COUNT");
            return false;
        }

        $pireps = $this->user->pireps()
            ->where('state', PirepState::ACCEPTED) // only count accepted PIREPs
            ->whereHas('airline', fn($q) => $q->where('icao', $airlineIcao))
            ->whereHas('aircraft', fn($q) => $q->where('icao', $aircraftIcao))
            ->with('fares')
            ->get();

        // Sum all fares with type = 1 (cargo)
        $totalCargo = $pireps->flatMap->fares
            ->filter(fn($fare) => (int)$fare->type === 1)
            ->sum('count');

        // Log for debugging
        Log::info("SPAwards(Cargo) | Pilot (ID: {$this->user->id}) carried {$totalCargo} cargo units with {$airlineIcao}/{$aircraftIcao}, {$requiredCargo} required.");

        return $totalCargo >= $requiredCargo;
    }
}
