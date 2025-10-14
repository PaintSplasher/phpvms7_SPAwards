<?php

namespace Modules\Awards\Awards;

use App\Contracts\Award;
use App\Models\Enums\PirepState;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SPAwardsNightowl extends Award
{
    public $name = 'SPAwards(Nightowl)';
    
    public $param_description = 'The number of night landings required for this award, between 22:00–06:00 UTC';

    public function check($requiredCount = null): bool
    {
        // Ensure parameter is provided and valid
        if (is_null($requiredCount) || !is_numeric($requiredCount)) {
            Log::error('SPAwards(Nightowl) | Invalid or missing parameter.');
            return false;
        }

        $requiredCount = (int) $requiredCount;

        // Retrieve all accepted PIREPs of the user
        $pireps = $this->user->pireps()
            ->where('state', PirepState::ACCEPTED) // only count accepted PIREPs
            ->get(['id', 'block_on_time', 'created_at']);

        // Count flights where the block-on time was between 22:00 and 06:00 UTC
        $nightLandings = $pireps->filter(function ($pirep) {
            // Use block_on_time if available, otherwise fallback to created_at
            $landingTime = $pirep->block_on_time 
                ? Carbon::parse($pirep->block_on_time) 
                : Carbon::parse($pirep->created_at);

            $hour = $landingTime->copy()->utc()->hour;

            // Night hours between 22:00–06:00 UTC
            return ($hour >= 22 || $hour < 6);
        })->count();

        // Log for debugging
        Log::info("SPAwards(Nightowl) | Pilot (ID: {$this->user->id}) has {$nightLandings} night landings, {$requiredCount} required.");

        // Return true if the required number of night landings is reached
        return $nightLandings >= $requiredCount;
    }
}
