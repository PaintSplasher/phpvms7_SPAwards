<?php

namespace Modules\Awards\Awards;

use App\Contracts\Award;
use App\Models\UserAward;
use App\Models\Enums\PirepState;
use Illuminate\Support\Facades\Log;

class SPAwardsPerformer extends Award
{
    public $name = 'SPAwards(Performer)';
    
    public $param_description = 'The minimum average flight score required to receive this award';

    public function check($minScore = null): bool
    {
        // Ensure parameter is provided and valid
        if (is_null($minScore) || !is_numeric($minScore)) {
            Log::error('SPAwards(Performer) | Invalid or missing minScore parameter.');
            return false;
        }

        // Check if the award is already granted
        $award = \App\Models\Award::where('ref_model', get_class($this))
            ->where('ref_model_params', (string) $minScore)
            ->first();

        if (!$award) {
            Log::error("SPAwards(Performer) | No matching award found.");
            return false;
        }

        $alreadyGranted = UserAward::where('user_id', $this->user->id)
            ->where('award_id', $award->id)
            ->exists();

        $minScore = (float) $minScore;

        // Retrieve average score from accepted PIREPs
        $avgScore = $this->user->pireps()
            ->where('state', PirepState::ACCEPTED)
            ->avg('score');

        // Handle pilots without any accepted PIREPs
        if (is_null($avgScore)) {
            Log::info("SPAwards(Performer) | Pilot (ID: {$this->user->id}) has no accepted flights yet.");

            // If they previously had it, remove it
            if ($alreadyGranted) {
                $this->user->awards()->detach($award->id);
                Log::info("SPAwards(Performer) | Award removed from Pilot (ID: {$this->user->id}) due to missing PIREPs.");
            }

            return false;
        }

        // Log for debugging
        Log::info("SPAwards(Performer) | Pilot (ID: {$this->user->id}) average score: {$avgScore},  {$minScore} required.");

        // Grant award if meets requirement
        if ($avgScore >= $minScore) {
            if (!$alreadyGranted) {
                $this->user->awards()->attach($award->id);
                Log::info("SPAwards(Performer) | Award granted to Pilot (ID: {$this->user->id}).");
                return true;
            }

            Log::info("SPAwards(Performer) | Award already granted to Pilot (ID: {$this->user->id}). Skipping...");
            return false;
        }

        // If falls below requirement, remove award if granted
        if ($alreadyGranted) {
            $this->user->awards()->detach($award->id);
            Log::info("SPAwards(Performer) | Award removed from Pilot (ID: {$this->user->id}) due to score drop.");
        }

        return false;
    }
}
