<?php

namespace Modules\Awards\Awards;

use App\Contracts\Award;
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
