<?php

namespace App\Models;

use App\Traits\HasSemanticSearch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use HasSemanticSearch;

    /**
     * The table associated with the model.
     */
    protected $table = 'audit_logs';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'causer_type',
        'causer_id',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'ip_address',
        'user_agent',
        'changes',
    ];

    /**
     * Get the causer (user) that performed the action.
     */
    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the subject (model) that was acted upon.
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function getSemanticText(): string
    {
        $this->loadMissing('causer');

        return implode(' ', array_filter([
            $this->action,
            $this->description,
            class_basename((string) $this->subject_type),
            $this->causer?->name,
            $this->causer?->username,
        ]));
    }

    /**
     * Cast the changes attribute to an array.
     * This ensures proper JSON decoding.
     */
    protected $casts = [
        'changes' => 'array',
    ];

    /**
     * Cast the changes attribute to an array.
     * This ensures proper JSON decoding even if the cast doesn't work.
     */
    public function getChangesAttribute($value)
    {
        if ($value === null) {
            return null;
        }

        // If it's already an array, return it
        if (is_array($value)) {
            return $value;
        }

        // If it's a string, try to decode it as JSON
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }

            // If JSON decoding failed, return the original value
            return $value;
        }

        // Return the value as-is for other types
        return $value;
    }

    /**
     * Get a plain attribute (not a relationship).
     * Override to ensure the accessor for changes is always called.
     */
    public function getAttribute($key)
    {
        if ($key === 'changes' && method_exists($this, 'getChangesAttribute')) {
            return $this->getChangesAttribute($this->getAttributeValue('changes'));
        }

        return parent::getAttribute($key);
    }
}
