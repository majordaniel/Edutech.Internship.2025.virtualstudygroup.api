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

    public function members()
    {
        return $this->hasMany(GroupMember::class, 'group_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'group_id');
    }
}
