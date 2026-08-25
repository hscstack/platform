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

        if ($request->boolean('remove_image')) {
            $rawImage = $notice->getRawOriginal('image');
            if ($rawImage && ! str($rawImage)->startsWith(['http://', 'https://'])) {
                Storage::delete($rawImage);
            }
            $data['image'] = null;
        }

        if ($request->hasFile('image')) {
            $rawImage = $notice->getRawOriginal('image');
            if ($rawImage && ! str($rawImage)->startsWith(['http://', 'https://'])) {
                Storage::delete($rawImage);
            }

            $path = $request->file('image')->store('notices');
            $data['image'] = $path;
        } else {
            if (! $request->boolean('remove_image')) {
                unset($data['image']);
            }
        }

        unset($data['remove_image']);

        $notice->update($data);

        return redirect()->route('admin.notice.edit')->with('success', 'Notice updated.');
    }
}
