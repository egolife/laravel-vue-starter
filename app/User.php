<?php

namespace App;

use App\Notifications\AdminPasswordReset;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Scout\Searchable;

class User extends Authenticatable
{
    use Notifiable;
    use Searchable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['first_name', 'last_name', 'username', 'email', 'password', 'active', 'meta'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];

    /**
     * Custom attributes
     * @var array
     */
    protected $appends = ['name', 'is_super_admin', 'is_user'];

    /**
     * Validation rules
     * @var array
     */
    public static $rules = [
        'first_name' => 'required|max:255',
        'last_name' => 'required|max:255',
        'username' => 'required|max:255|unique:users|unique:members',
        'email' => 'required|email|max:255|unique:users|unique:members',
        'password' => 'required|min:6|confirmed',
    ];

    /**
     * Get default user role
     * @return string
     */
    public static function getDefaultRole()
    {
        return static::first() ? 'User' : 'Super Administrator';
    }

    /**
     * 'name' accessor
     * @return string
     */
    public function getNameAttribute()
    {
        $name = "$this->first_name " . strtoupper($this->last_name);
        return $name;
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new AdminPasswordReset($token, $this));
    }

    /**
     * 'is_super_admin' accessor
     * @return bool
     */
    public function getIsSuperAdminAttribute()
    {
        $meta = json_decode($this->meta);

        if ( $meta && property_exists($meta, 'role') )
            return strtolower($meta->role) == 'super administrator';

        return false;
    }

    /**
     * 'is_user' accessor
     * @return bool
     */
    public function getIsUserAttribute()
    {
        $meta = json_decode($this->meta);

        if ( $meta && property_exists($meta, 'role') )
            return strtolower($meta->role) == 'user';

        return false;
    }

    /**
     * Find user by id
     * @param $id
     * @return mixed
     */
    public static function findResource($id)
    {
        return static::find($id);
    }

    /***
     * Get resources of specified ids
     * @param $ids
     * @param string $orderBy
     * @param string $order
     * @return mixed
     */
    public static function getResourcesByIds($ids, $orderBy = 'first_name', $order = 'asc')
    {
        return static::whereIn('id', (array) $ids)->orderBy($orderBy, $order)->get();
    }

    /**
     * Get all resources with no pagination
     * @param string $orderBy
     * @param string $order
     * @return mixed
     */
    public static function getResourcesNoPagination($orderBy = 'first_name', $order = 'asc')
    {
        return static::orderBy($orderBy, $order)->get();
    }

    /**
     * Get all resources
     * @param string $orderBy
     * @param string $order
     * @param int $paginate
     * @param array $except
     * @return mixed
     */
    public static function getResources($orderBy = 'first_name', $order = 'asc', $paginate = 25, $except = [])
    {
        return static::whereNotIn('id', $except)->orderBy($orderBy, $order)->paginate($paginate);
    }

    /**
     * Get search results
     * @param $search
     * @param int $paginate
     * @param array $except
     * @return mixed
     */
    public static function getSearchResults($search, $paginate = 25, $except = [])
    {
        return static::whereIn('id', static::search($search)->get()->pluck('id'))->whereNotIn('id', $except)->paginate($paginate);
    }

}
