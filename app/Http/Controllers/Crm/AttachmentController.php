<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StoreAttachmentRequest;
use App\Models\Attachment;
use App\Services\Crm\AttachmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function __construct(private readonly AttachmentService $attachments) {}

    public function store(StoreAttachmentRequest $request): RedirectResponse
    {
        $this->attachments->attach($request->attachable(), $request->file('file'), $request->user());

        $this->toast('File uploaded.');

        return back();
    }

    public function download(Attachment $attachment): StreamedResponse
    {
        return $this->attachments->download($attachment);
    }

    public function destroy(Attachment $attachment): RedirectResponse
    {
        Gate::authorize('delete', $attachment);

        $this->attachments->remove($attachment);

        $this->toast('File removed.');

        return back();
    }
}
