<?php

namespace App\Http\Middleware;

use App\Models\School;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSchoolMembership
{
    /**
     * Ensure the authenticated user is an active member of the routed school.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $school = $request->route('school');

        abort_unless($school instanceof School, 404);

        $isActiveMember = $request->user()
            ->schoolMemberships()
            ->where('school_id', $school->id)
            ->where('status', 'active')
            ->exists();

        abort_unless($isActiveMember, 403);

        $request->attributes->set('active_school', $school);

        return $next($request);
    }
}
