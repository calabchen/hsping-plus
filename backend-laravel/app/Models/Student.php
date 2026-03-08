<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
  use HasFactory;

  protected $table = 'students';
  protected $primaryKey = 'student_id';
  public $incrementing = false;
  protected $keyType = 'string';

  protected $fillable = [
    'student_id',
    'class_id',
    'last_name',
    'first_name',
    'gender',
    'age',
    'avatar_path',
  ];
}
