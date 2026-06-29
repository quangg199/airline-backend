<?php
namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public function getAll($search = null)
    {
        return User::with('roles')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%$search%")
                          ->orWhere('email', 'like', "%$search%");
                });
            })
            ->latest()
            ->paginate(10); // ✅ 10 rows per page
    }

    public function find($id)
    {
        return User::with('roles')->findOrFail($id);
    }

    public function create(array $data)
    {
        return User::create($data);
    }

    public function update(User $user, array $data)
    {
        $user->update($data);
        return $user->fresh();
    }

    public function delete(User $user)
    {
        return $user->delete();
    }
}