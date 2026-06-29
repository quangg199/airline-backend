<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;

class UserAdminController extends Controller
{
    public function __construct(
        protected UserRepository $repo
    ) {}

    // GET /admin/users
    public function index(Request $request)
    {
        $users = $this->repo->getAll($request->search);

        $users->getCollection()->transform(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,

                // FIX ROLE
                'role' => $user->roles->first()?->name ?? 'user',
            ];
        });

        return response()->json($users);
    }

    // GET ONE
    public function show($id)
    {
        $user = $this->repo->find($id);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->roles->first()?->name ?? 'user',
        ]);
    }

    // CREATE
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
        ]);

        $user = $this->repo->create($request->all());

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => 'user',
        ]);
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $updated = $this->repo->update($user, $request->all());

        return response()->json([
            'id' => $updated->id,
            'name' => $updated->name,
            'email' => $updated->email,
            'role' => $updated->roles->first()?->name ?? 'user',
        ]);
    }

    // DELETE
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $this->repo->delete($user);

        return response()->json([
            'message' => 'Deleted successfully'
        ]);
    }
}