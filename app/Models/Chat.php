<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Chat extends Model
{
    use HasFactory;
    protected $fillable = ['user_id','title','metadata'];
    protected $casts = ['metadata'=>'array'];

    public function messages() { return $this->hasMany(AiChatMessage::class)->orderBy('id'); }
    public function files() { return $this->hasMany(AiChatFiles::class); }
    public function user() { return $this->belongsTo(User::class); }
}
