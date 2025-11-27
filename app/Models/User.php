<php?

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Role;

class User extends Authenticatable
{
    // ...

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);                                                                                                                                                                                                                                               
    }

    public function isRole(string $role): bool
    {
        return $this->role && $this->role->name === $role;
    }
}
