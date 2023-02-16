<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

/**
 * App\Models\TelegramUsers
 *
 * @property int $id
 * @property int $mb_uid
 * @property string $token
 * @property string|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUsers newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUsers newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUsers query()
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUsers whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUsers whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUsers whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUsers whereMbUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUsers whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUsers whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property string|null $username
 * @property string|null $last_name
 * @property string|null $first_name
 * @property string|null $phone
 * @property string $language
 * @method static \Illuminate\Database\Query\Builder|TelegramUsers onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUsers whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUsers whereLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUsers whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUsers wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUsers whereUsername($value)
 * @method static \Illuminate\Database\Query\Builder|TelegramUsers withTrashed()
 * @method static \Illuminate\Database\Query\Builder|TelegramUsers withoutTrashed()
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 */
class TelegramUsers extends Model
{
    use SoftDeletes;
    use Notifiable;

    protected $fillable = [
        'id',
        'token',
        'mb_uid',
        'username',
        'last_name',
        'first_name',
        'language',
    ];

    use HasFactory;
}
