<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description'
    ];  

    public function studyGroups()
    {
        return $this->hasMany(StudyGroup::class);
    }

    public function students()
    {
        return $this->belongsToMany(User::class, 'course_user');
    }

    
}
