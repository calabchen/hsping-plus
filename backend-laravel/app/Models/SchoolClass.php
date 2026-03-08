<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
  use HasFactory;

  protected $table = 'classes';
  protected $primaryKey = 'class_id';

  protected $fillable = [
    'class_num',
    'enrollment_year',
    'graduation_year',
    'is_graduated',
  ];

  protected $casts = [
    'is_graduated' => 'boolean',
  ];
}
