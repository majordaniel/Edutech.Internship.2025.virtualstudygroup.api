<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class group_members_table extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'group_id',
        'student_id',
        'course_code',
        'role',
    ];
    public function student()
    {
        // return $this->belongsTo(students::class, 'student_id');
        return $this->belongsTo(User::class, 'student_id');
    }
}
