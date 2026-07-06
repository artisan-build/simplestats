# Eloquent & Laravel Best Practices

Prefer Laravel's expressive, model-aware APIs over manual ID plumbing. Passing models instead of raw IDs is safer (correct key resolution, route model binding) and reads better. Each section shows the pattern to avoid and the one to use.

## Routing

Pass the model itself to `route()`, not its key. Laravel resolves the route key automatically (and respects custom route keys).

```php
// Avoid
route('users.show', $user->id);

// Prefer
route('users.show', $user);
```

## Creating Related Models

Create through the relationship instead of setting the foreign key by hand.

```php
// Avoid
Post::create(['user_id' => $user->id, ...$attributes]);

// Prefer
$user->posts()->create($attributes);
```

## BelongsToMany / Pivot

`attach()` and `updateExistingPivot()` accept models directly.

```php
// Avoid
$user->roles()->attach($role->id);

// Prefer
$user->roles()->attach($role);
```

```php
// Avoid
$user->roles()->where('roles.id', $role->id)->update([...]);

// Prefer
$user->roles()->updateExistingPivot($role, [...]);
```

## Querying by Relationship

Use `whereBelongsTo()` and `whereKey()` instead of hand-written foreign key or primary key clauses.

```php
// Avoid
Post::where('user_id', $user->id)->get();

// Prefer
Post::whereBelongsTo($user)->get();
```

```php
// Avoid
$user->posts()->where('id', $post->id)->exists();

// Prefer
$user->posts()->whereKey($post)->exists();
```

## Comparing Models

Use `is()` (and `isNot()`) to compare related models instead of comparing IDs.

```php
// Avoid
if ($post->user_id === $user->id) {
    // ...
}

// Prefer
if ($post->user()->is($user)) {
    // ...
}
```

## Associating BelongsTo Relations

Use `associate()` instead of writing the foreign key directly.

```php
// Avoid
$post->update(['user_id' => $user->id]);

// Prefer
$post->user()->associate($user)->save();
```

## Timestamp Columns

Use `touch()` to set a timestamp column to now.

```php
// Avoid
$project->update(['cancelled_at' => now()]);

// Prefer
$project->touch('cancelled_at');
```

## Authorization Guards

Use `Gate::allowIf()` / `Gate::denyIf()` for inline authorization checks instead of manual `abort(403)`.

```php
// Avoid
if (! $admin) {
    abort(403);
}

// Prefer
Gate::allowIf($admin);
```
