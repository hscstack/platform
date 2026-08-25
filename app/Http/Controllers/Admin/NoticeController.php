<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notice\UpdateNoticeRequest;
use App\Models\Notice;
use Illuminate\Support\Facades\Storage;
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
        $notice = Notice::singleton();
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $oldImage = $notice->getRawOriginal('image');
            if ($oldImage && ! str($oldImage)->startsWith(['http://', 'https://'])) {
                Storage::delete($oldImage);
            }

            $path = $request->file('image')->store('notices');
            $data['image'] = $path;
        } elseif ($request->boolean('remove_image')) {
            $oldImage = $notice->getRawOriginal('image');
            if ($oldImage && ! str($oldImage)->startsWith(['http://', 'https://'])) {
                Storage::delete($oldImage);
            }
            $data['image'] = null;
        } else {
            unset($data['image']);
        }

        unset($data['remove_image']);

        $notice->update($data);

        return redirect()->route('admin.notice.edit')->with('success', 'Notice updated.');
    }
}
