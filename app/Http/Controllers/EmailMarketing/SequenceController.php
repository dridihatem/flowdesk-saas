<?php

namespace App\Http\Controllers\EmailMarketing;

use App\Http\Controllers\Controller;
use App\Models\EmailMarketingSequence;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SequenceController extends Controller
{
    public function index(Request $request): View
    {
        abort_if(! $request->user()->company, 403);

        $sequences = EmailMarketingSequence::query()
            ->orderByDesc('updated_at')
            ->paginate(15);

        return view('email-marketing.sequences.index', compact('sequences'));
    }
}
