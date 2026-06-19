<?php
namespace App\Services;

use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(
        protected UserRepository $repo
    ) {}

    public function list($search)
    {
        return $this->repo->getAll($search);
    }

    public function store($data)
    {
        $data['password'] = Hash::make($data['password']);
        return $this->repo->create($data);
    }

    public function update($user, $data)
    {
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return $this->repo->update($user, $data);
    }

    public function delete($user)
    {
        return $this->repo->delete($user);
    }
}