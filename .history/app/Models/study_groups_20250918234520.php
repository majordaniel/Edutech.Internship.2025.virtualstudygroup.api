<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class study_groups extends Model
{
    public function members()
    {
        return $this->hasMany(GroupMember::class, 'group_id');
    }
}
