<?php

namespace Modules\Awards\Awards;

use App\Contracts\Award;
use App\Models\Enums\PirepState;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SPAwardsEarlybird extends Award
{
    public $name = 'SPAwards(Earlybird)';
    
    public $param_description = 'The number of early morning flights (04:00–08:00 UTC) required for this award';

    public function check($requiredFlights = null): bool
    {
        // Validate parameter
        if (is_null($requiredFlights) || !is_numeric($requiredFlights)) {
            Log::error('SPAwards(Earlybird) | Invalid or missing requiredFlights parameter.');
            return false;
        }

        $requiredFlights = (int) $requiredFlights;

        // Fetch all accepted PIREPs
        $pireps = $this->user->pireps()
            ->where('state', PirepState::ACCEPTED) // only count accepted PIREPs
            ->get(['id', 'block_off_time', 'created_at']);

        // Count early flights (04:00–08:00 UTC)
        $earlyFlights = $pireps->filter(function ($pirep) {
            $departureTime = $pirep->block_off_time 
                ? Carbon::parse($pirep->block_off_time) 
                : Carbon::parse($pirep->created_at);

            $hour = $departureTime->copy()->utc()->hour;
            return $hour >= 4 && $hour < 8; // between 04:00–07:59 UTC
        })->count();

        // Debug log
        Log::info("SPAwards(Earlybird) | Pilot (ID: {$this->user->id}) has {$earlyFlights} early flights, {$requiredFlights} required.");

        // Check if pilot meets the requirement
        return $earlyFlights >= $requiredFlights;
    }
}
