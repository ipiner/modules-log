<?php

namespace Pin\Tests\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Pin\Models\Concerns\SoftDeletes;
use Pin\Models\Model;
use Pin\Modules\Log\Models\Concerns\HasOperationLog;

/**
 * @property string $username
 * @property string $password
 */
class User extends Model implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword, MustVerifyEmail;
    use HasOperationLog, SoftDeletes;

    public function subjectNameColumn()
    {
        return ['name', 'username'];
    }
}
