<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupMessage extends Model
{
    protected $fillable = ['group_id','user_id', 'message', 'file_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function group()
    {
        return $this->belongsTo(study_groups::class);
    }

    public function file()
    {
        return $this->belongsTo(files::class);
    }
}
