<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLogin extends Model{

	protected $table = 'userlogin';
	protected $primaryKey = 'username';
	public $timestamps = false;

	protected $fillable = [];

	protected $hidden = [ 'password' ];

	public function user() {
		return $this->belongsTo(User::class, 'uid', 'uid');
	}
}