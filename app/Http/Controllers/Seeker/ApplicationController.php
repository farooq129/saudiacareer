<?php

namespace App\Http\Controllers\Seeker;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Everything a seeker has applied to, and what came of it. */
class ApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'all');

        return view('seeker.applications.index', [
            'applications' => Application::query()
                ->where('user_id', $request->user()->id)
                ->when($status !== 'all', fn ($q) => $q->where('status', $status))
                ->with(['job.company', 'job.city', 'job.category'])
                ->latest()
                ->paginate(25)
                ->withQueryString(),
            'status' => $status,
        ]);
    }
}
