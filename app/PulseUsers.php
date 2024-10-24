<?php

namespace App;

use App\Models\Instance;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Laravel\Pulse\Contracts\ResolvesUsers;

class PulseUsers implements ResolvesUsers
{
    /**
     * The resolved users.
     *
     * @var Collection<int, \Illuminate\Contracts\Auth\Authenticatable>
     */
    protected Collection $resolvedUsers;

    /**
     * The field resolver.
     *
     * @var ?callable(\Illuminate\Contracts\Auth\Authenticatable): object{name: string, extra?: string, avatar?: string}
     */
    protected $fieldResolver = null;

    /**
     * Return a unique key identifying the user.
     */
    public function key(Authenticatable $user): int|string|null
    {
        return get_class($user).':'.$user->getAuthIdentifier();
    }

    /**
     * Eager load the users with the given keys.
     *
     * @param  Collection<int, int|string|null>  $keys
     */
    public function load(Collection $keys): self
    {
        $this->resolvedUsers = new Collection();

        $groupedKeys = $keys->mapToGroups(function ($key) {
            [$model, $id] = explode(':', $key);
            return [$model => $id];
        });

        foreach ($groupedKeys as $model => $ids) {
            $this->resolvedUsers[$model] = $model::withoutGlobalScopes()->findMany($ids);
        }

        return $this;
    }

    /**
     * Find the user with the given key, parsing the model and ID.
     *
     * @return object{name: string, extra?: string, avatar?: string}
     */
    public function find(int|string|null $key): object
    {
        [$model, $id] = explode(':', $key);

        $user = $this->resolvedUsers[$model]->first(fn ($user) => $user->getAuthIdentifier() == $id);

        if ($this->fieldResolver !== null && $user !== null) {
            return (object) ($this->fieldResolver)($user);
        }

        return (object) [
            'name' => $user->name ?? "ID: $id",
            'extra' => explode('\\', $model)[2],
            'avatar' => $user->avatar ?? (($user->email ?? false)
                    ? sprintf('https://gravatar.com/avatar/%s?d=mp', hash('sha256', trim(strtolower($user->email)))) // @phpstan-ignore property.nonObject
                    : sprintf('https://gravatar.com/avatar?d=mp')
                ),
        ];
    }

    /**
     * Override the field resolver.
     *
     * @param  callable(\Illuminate\Contracts\Auth\Authenticatable): object{name: string, extra?: string, avatar?: string}  $resolver
     */
    public function setFieldResolver(callable $resolver): self
    {
        $this->fieldResolver = $resolver;

        return $this;
    }
}
