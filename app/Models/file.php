<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class file extends Model
{
    protected $fillable = ['group_id', 'uploaded_by', 'name', 'path'];

    protected $table = 'files';

     public function studyGroup()
    {
        return $this->belongsTo(study_groups::class, 'group_id');
    }

    public function group()
    {
        return $this->belongsTo(StudyGroup::class, 'group_id');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
