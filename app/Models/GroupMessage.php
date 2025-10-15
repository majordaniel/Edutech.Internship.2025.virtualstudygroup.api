<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupMessage extends Model
{
    use HasFactory;

    protected $fillable = ['group_id','user_id', 'message', 'file_id', 'call_id',];

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

    public function meeting()
    {
        return $this->belongsTo(group_meetings_table::class, 'call_id');
    }
}
