<?php

namespace Laravilt\Panel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class LocaleController extends Controller
{
    /**
     * Update the user's locale.
     */
    public function update(Request $request)
    {
        $request->validate([
            'locale' => ['required', 'string', 'max:10'],
        ]);

        $user = $request->user();

        if ($user) {
            $user->update([
                'locale' => $request->input('locale'),
            ]);
        }

        return back();
    }
}
