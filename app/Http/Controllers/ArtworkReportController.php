<?php

namespace App\Http\Controllers;

use App\Models\ArtworkReport;
use App\Models\Product as Artwork;
use Illuminate\Http\Request;

class ArtworkReportController extends Controller
{
    public function store(Request $request, Artwork $artwork)
    {
        abort_unless(session('user_id'), 403);

        $data = $request->validate([
            'reason' => ['required', 'in:copyright,inappropriate,spam,other'],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        ArtworkReport::firstOrCreate(
            ['product_id' => $artwork->id, 'reporter_id' => session('user_id'), 'status' => 'open'],
            $data
        );

        return back()->with('success', 'Report submitted.');
    }
}
