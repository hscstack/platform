<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notice\UpdateNoticeRequest;
use App\Models\Notice;
use Inertia\Inertia;

class NoticeController extends Controller
{
    public function edit()
    {
        return Inertia::render('admin/NoticeEdit', [
            'notice' => Notice::singleton(),
        ]);
    }

    public function update(UpdateNoticeRequest $request)
    {

        Notice::singleton()->update($request->validated());

        return redirect()->route('admin.notice.edit')->with('success', 'Notice updated.');
    }
}
