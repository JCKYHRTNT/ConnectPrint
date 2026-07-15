<?php

namespace App\Http\Controllers;

use App\Models\Product as Artwork;
use App\Models\User;

class CreatorController extends Controller
{
    public function show(User $user)
    {
        return view('creators.show', [
            'creator' => $user,
            'artworks' => Artwork::with('category')
                ->where('user_id', $user->id)
                ->where('visibility', 'public')
                ->where('moderation_status', 'approved')
                ->whereNull('archived_at')
                ->latest()
                ->paginate(12),
        ]);
    }
}
