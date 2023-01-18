<?php
namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject{
	use Authenticatable, Authorizable, HasFactory;

	protected $table = 'users';
	protected $primaryKey = 'uid';

	public $timestamps = false;

	protected $fillable = [ 'name', 'email' ];

	public function getAuthPassword(){
		return $this->userLogin->password;
	}

	public function userLogin(){
		return $this->hasOne(UserLogin::class, 'uid', 'uid');
	}

	public function getJWTIdentifier(){
		return $this->getKey();
	}

	public function getJWTCustomClaims(){
		return [];
	}
}
