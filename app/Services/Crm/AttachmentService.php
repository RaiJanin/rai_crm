<?php

namespace App\Services\Crm;

use App\Contracts\Attachable;
use App\Models\Attachment;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentService
{
    /**
     * Store an uploaded file under attachments/<record type>/ and link it to the record.
     */
    public function attach(Attachable $record, UploadedFile $file, User $uploader): Attachment
    {
        $attachment = new Attachment([
            'uploaded_by' => $uploader->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $file->store('attachments/'.$record->getMorphClass(), Attachment::DISK),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
        ]);

        $record->attachments()->save($attachment);

        return $attachment;
    }

    public function download(Attachment $attachment): StreamedResponse
    {
        /** @var FileSystemAdapter $disk */
        $disk = Storage::disk(Attachment::DISK);

        return $disk->download($attachment->file_path, $attachment->file_name);
    }

    /**
     * Delete the record and its stored file.
     */
    public function remove(Attachment $attachment): void
    {
        $attachment->forceDelete();
    }
}
