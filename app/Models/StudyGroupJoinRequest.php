<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudyGroupJoinRequest extends Model
{
    use HasFactory;

    protected $fillable = ['group_id', 'user_id', 'status'];

    public function studyGroup()
    {
        return $this->belongsTo(study_groups::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

