<?php

namespace Modules\Awards\Awards;

use App\Contracts\Award;
use App\Models\Enums\PirepState;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SPAwardsWeekend extends Award
{
    public $name = 'SPAwards(Weekend)';
    
    public $param_description = 'The number of weekend flights (Saturday + Sunday) required to receive this award';

    public function check($requiredCount = null): bool
    {
        // Ensure parameter is valid
        if (is_null($requiredCount) || !is_numeric($requiredCount)) {
            Log::error('SPAwards(Weekend) | Invalid or missing parameter.');
            return false;
        }

        $requiredCount = (int) $requiredCount;

        // Retrieve all accepted PIREPs for this user
        $pireps = $this->user->pireps()
            ->where('state', PirepState::ACCEPTED) // only count accepted PIREPs
            ->get(['id', 'block_off_time', 'created_at']);

        // Count those flown on weekends (Saturday = 6, Sunday = 0)
        $weekendFlights = $pireps->filter(function ($pirep) {
            $flightTime = $pirep->block_off_time 
                ? Carbon::parse($pirep->block_off_time)
                : Carbon::parse($pirep->created_at);
            $dayOfWeek = $flightTime->copy()->utc()->dayOfWeek;
            return $dayOfWeek === Carbon::SATURDAY || $dayOfWeek === Carbon::SUNDAY;
        })->count();

        // Log for debugging
        Log::info("SPAwards(Weekend) | Pilot (ID: {$this->user->id}) has {$weekendFlights} weekend flights, {$requiredCount} required.");

        // Return true if the required number of weekend flights is reached
        return $weekendFlights >= $requiredCount;
    }
}
