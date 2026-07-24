<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\RoleGuideService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DocumentationController extends Controller
{
    public function __construct(private RoleGuideService $guides) {}

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return Inertia::render('documentation/Index', [
            'guides' => $this->guides->for($user),
        ]);
    }
}
