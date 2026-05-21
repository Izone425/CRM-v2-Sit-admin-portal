<?php

namespace App\Models\Ticketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreativeRequestAttachment extends Model
{
    protected $connection = 'ticketingsystem_live';
    protected $table = 'creative_request_attachments';

    protected $fillable = [
        'request_id', 'attachment_type', 'type',
        'original_filename', 'stored_filename', 'file_path',
        'file_size', 'mime_type', 'file_hash',
        'uploaded_by', 'url', 'title', 'description',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(TicketingUser::class, 'uploaded_by');
    }

    public function creativeRequest(): BelongsTo
    {
        return $this->belongsTo(CreativeRequest::class, 'request_id');
    }
}
