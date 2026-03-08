<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Teacher extends Model
{
  use HasFactory;

  protected $primaryKey = 'teacher_id';

  protected $fillable = [
    'user_id',
    'last_name',
    'first_name',
    'gender',
    'education_stage',
    'subject',
  ];

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class, 'user_id');
  }
}
