<?php

namespace App\Models;

use App\Models\group_members_table as GroupMember;
use Illuminate\Database\Eloquent\Model;

class study_groups extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'group_id',
        'group_name',
        'course_id',
        'created_by',
        'description',
    ];

    public function files()
    {
        return $this->hasMany(File::class, 'group_id');
    }


    public function messages()
    {
        return $this->hasMany(GroupMessage::class, 'group_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'group_members_tables', 'study_group_id', 'student_id')
            ->withPivot('course_code', 'role')
            ->withTimestamps();
    }

    public function members()
    {
        return $this->hasMany(group_members_table::class, 'group_id');
    }
}
