<?php

namespace Modules\Awards\Awards;

use App\Contracts\Award;
use Illuminate\Support\Facades\Log;

class SPAwardsRoutecode extends Award
{
    public $name = 'SPAwards(RouteCode)';
    
    public $param_description = 'The route code of the flight a pilot has completed to give this award';

    public function check($routecode = null): bool
    {
        // Ensure a valid route code parameter is provided
        if (is_null($routecode)) {
            Log::error('SPAwards(RouteCode) | No parameter set.');
            return false;
        }

        // Ensure the user has a valid last PIREP (pilot report)
        if (empty($this->user->last_pirep_id) || empty($this->user->last_pirep)) {
            Log::warning("SPAwards(RouteCode) | User {$this->user->id} has no last PIREP.");
            return false;
        }

        // Retrieve the route code from the user's last PIREP
        $userRouteCode = $this->user->last_pirep->route_code ?? null;

        // If the route code is missing, log and fail
        if (is_null($userRouteCode)) {
            Log::warning("SPAwards(RouteCode) | User {$this->user->id}'s last PIREP has no route code.");
            return false;
        }

        // Log for debugging
        Log::info("SPAwards(RouteCode) | Pilot (ID: {$this->user->id}) has flown {$userRouteCode}, {$routecode} needed.");

        // Compare the user's last flight route code with the required one
        return $userRouteCode === $routecode;
    }
}