<?php

namespace Modules\Awards\Awards;

use App\Contracts\Award;
use App\Models\UserAward;
use App\Models\Enums\PirepState;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SPAwardsConsecutive extends Award
{
    public $name = 'SPAwards(Consecutive)';
    
    public $param_description = 'The number of consecutive days with at least one accepted flight';

    public function check($days = null): bool
    {
        // Ensure parameter is provided and valid
        if (is_null($days)) {
            Log::error('SPAwards(Consecutive) | No parameter set.');
            return false;
        }

        // Check if the award is already granted
        $award = \App\Models\Award::where('ref_model', get_class($this))
            ->where('ref_model_params', (string) $days)
            ->first();

        if (!$award) {
            Log::error("SPAwards(Consecutive) | No matching award found.");
            return false;
        }

        $alreadyGranted = UserAward::where('user_id', $this->user->id)
            ->where('award_id', $award->id)
            ->exists();

        if ($alreadyGranted) {
            Log::info("SPAwards(Consecutive) | Award already granted to Pilot (ID: {$this->user->id}). Skipping...");
            return false;
        }

        $requiredDays = (int) $days;

        // Retrieve all accepted PIREPs of the user (only submission date is relevant)
        $pirepDates = $this->user->pireps()
            ->where('state', PirepState::ACCEPTED)
            ->orderByDesc('created_at')
            ->pluck('created_at')
            ->map(fn ($date) => Carbon::parse($date)->toDateString()) // convert to Y-m-d
            ->unique()
            ->values();

        // If the pilot has fewer flight days than required, fail early
        if ($pirepDates->count() < $requiredDays) {
            Log::info("SPAwards(Consecutive) | Pilot (ID: {$this->user->id}) {$pirepDates->count()} active days, {$requiredDays} required.");
            return false;
        }

        // Track consecutive streak
        $streak = 1;

        for ($i = 0; $i < $pirepDates->count() - 1; $i++) {
            $currentDay = Carbon::parse($pirepDates[$i]);
            $nextDay = Carbon::parse($pirepDates[$i + 1]);

            // If next flight is exactly one day before current flight -> continue streak
            if ($nextDay->diffInDays($currentDay) === 1) {
                $streak++;
                if ($streak >= $requiredDays) {
                    Log::info("SPAwards(Consecutive) | Pilot (ID: {$this->user->id}) achieved a {$streak}-day streak.");
                    return true;
                }
            } else {
                // Streak broken, reset counter
                $streak = 1;
            }
        }

        // Log and return false if streak was not reached
        Log::info("SPAwards(Consecutive) | Pilot (ID: {$this->user->id}) longest streak: {$streak} days, {$requiredDays} required.");
        return false;
    }
}
