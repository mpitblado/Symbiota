<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAccessToken extends Model{

	protected $table = 'useraccesstokens';
	protected $primaryKey = 'tokid';
	public $timestamps = false;

	protected $fillable = [];

	protected $hidden = [];

	public function user() {
		return $this->belongsTo(User::class, 'uid', 'uid');
	}
}