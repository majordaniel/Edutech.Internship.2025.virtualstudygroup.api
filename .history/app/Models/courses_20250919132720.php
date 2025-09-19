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
}
