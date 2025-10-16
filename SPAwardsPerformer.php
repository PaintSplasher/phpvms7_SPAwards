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

        if ($alreadyGranted) {
            Log::info("SPAwards(Performer) | Award already granted to Pilot (ID: {$this->user->id}). Skipping...");
            return false;
        }

        $minScore = (float) $minScore;

        // Retrieve average score from accepted PIREPs
        $avgScore = $this->user->pireps()
            ->where('state', PirepState::ACCEPTED)
            ->avg('score');

        // Handle pilots without any accepted PIREPs
        if (is_null($avgScore)) {
            Log::info("SPAwards(Performer) | Pilot (ID: {$this->user->id}) has no accepted flights yet.");
            return false;
        }

        // Log for debugging
        Log::info("SPAwards(Performer) | Pilot (ID: {$this->user->id}) average score: {$avgScore},  {$minScore} required.");

        // Return true if the pilot's average meets or exceeds requirement
        return $avgScore >= $minScore;
    }
}
