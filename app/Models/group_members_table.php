<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

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
        return $this->belongsTo(User::class, 'student_id');
    }
}
