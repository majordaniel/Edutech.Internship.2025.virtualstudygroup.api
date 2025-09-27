<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class courses extends Model
{
    use HasFactory;

    protected $fillable = ['course_id', 'course_name',
    'course_code', 'course_description', 'Credit_units',
    'semester', 'level', 'department',];

    /**
     * Return a new factory instance for the model.
     * This is needed because the model class name does not follow
     * the typical StudlyCase convention and the factory class is
     * named CourseFactory.
     */
    protected static function newFactory()
    {
        return \Database\Factories\CourseFactory::new();
    }
}
