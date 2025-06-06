<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'parent_id',
        'level',
        'sort_order'
    ];
    public function parent()
    {
        return $this->belongsTo(Activity::class, 'parent_id');
    }

    // Relasi children
    public function children()
    {
        return $this->hasMany(Activity::class, 'parent_id')->orderBy('sort_order');
    }

    // Semua descendants (rekursif)
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    // Scope untuk root activities (tanpa parent)
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    // Scope untuk ordered (berdasarkan hierarki)
    public function scopeOrdered($query)
    {
        return $query->orderBy('parent_id')->orderBy('sort_order')->orderBy('name');
    }

    // Helper untuk mendapatkan indentasi
    public function getIndentedNameAttribute()
    {
        $indent = str_repeat('— ', $this->level);
        return $indent . $this->name;
    }

    // Helper untuk check apakah root
    public function isRoot()
    {
        return is_null($this->parent_id);
    }

    // Helper untuk check apakah ada children
    public function hasChildren()
    {
        return $this->children()->count() > 0;
    }
}
