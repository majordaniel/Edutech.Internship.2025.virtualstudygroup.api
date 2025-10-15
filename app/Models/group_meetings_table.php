<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class group_meetings_table extends Model
{
    protected $table = 'group_meetings_tables';

    use HasFactory;

    protected $fillable = [
        'host_id',
        'group_id',
        'meeting_date',
        'meeting_time',
        'meeting_link',
    ];
}
