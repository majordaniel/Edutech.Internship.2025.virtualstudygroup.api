<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class group_members_table extends Model
{
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
