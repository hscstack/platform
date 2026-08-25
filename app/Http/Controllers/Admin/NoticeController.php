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
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('notices');
        } else {
            unset($data['image']);
        }

        Notice::singleton()->update($data);

        return redirect()->route('admin.notice.edit')->with('success', 'Notice updated.');
    }
}
