<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $attachment_id
 * @property string $attachable_type
 * @property int $attachable_id
 * @property int|null $uploaded_by
 * @property string $file_name
 * @property string $file_path
 * @property string|null $mime_type
 * @property int $file_size
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Table(key: 'attachment_id')]
#[Fillable(['uploaded_by', 'file_name', 'file_path', 'mime_type', 'file_size'])]
class Attachment extends CrmModel
{
    use SoftDeletes;

    public const DISK = 'public';

    public function label(): string
    {
        return $this->file_name;
    }

    protected static function booted(): void
    {
        static::forceDeleted(function (Attachment $attachment) {
            Storage::disk(self::DISK)->delete($attachment->file_path);
        });
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
