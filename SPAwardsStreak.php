<?php

namespace Modules\Awards\Awards;

use App\Contracts\Award;
use App\Models\UserAward;
use App\Models\Enums\PirepState;
use Illuminate\Support\Facades\Log;

class SPAwardsStreak extends Award
{
    public $name = 'SPAwards(Streak)';
    
    public $param_description = 'The number of consecutive accepted flights required to give this award';

    public function check($streakCount = null): bool
    {
        // Ensure parameter is provided and valid
        if (is_null($streakCount) || !is_numeric($streakCount)) {
            Log::error('SPAwards(Streak) | Invalid or missing streak parameter.');
            return false;
        }

        // Check if the award is already granted
        $award = \App\Models\Award::where('ref_model', get_class($this))
            ->where('ref_model_params', (string) $streakCount)
            ->first();

        if (!$award) {
            Log::error("SPAwards(Streak) | No matching award found.");
            return false;
        }

        $alreadyGranted = UserAward::where('user_id', $this->user->id)
            ->where('award_id', $award->id)
            ->exists();

        if ($alreadyGranted) {
            Log::info("SPAwards(Streak) | Award already granted to Pilot (ID: {$this->user->id}). Skipping...");
            return false;
        }

        $requiredStreak = (int) $streakCount;

        // Retrieve user's PIREPs in descending order (latest first)
        $pireps = $this->user->pireps()
            ->orderByDesc('created_at')
            ->take($requiredStreak)
            ->get(['state', 'id', 'created_at']);

        // If user doesn't have enough PIREPs yet, stop here
        if ($pireps->count() < $requiredStreak) {
            Log::info("SPAwards(Streak) | Pilot (ID: {$this->user->id}) has only {$pireps->count()} flights, {$requiredStreak} required.");
            return false;
        }

        // Check that all recent PIREPs are accepted (no rejections or cancellations)
        $isPerfectStreak = $pireps->every(function ($pirep) {
            return $pirep->state === PirepState::ACCEPTED; // only count accepted PIREPs
        });

        // Log for debugging
        Log::info("SPAwards(Streak) | Pilot (ID: {$this->user->id}) streak check: " . ($isPerfectStreak ? 'PASSED' : 'FAILED'));

        return $isPerfectStreak;
    }
}
