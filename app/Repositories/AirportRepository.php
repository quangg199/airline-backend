<?php

namespace App\Repositories;

use App\Models\Airport;
use App\Repositories\Interfaces\AirportRepositoryInterface;

class AirportRepository implements AirportRepositoryInterface
{
    public function getAll()
    {
        return Airport::orderBy('id', 'desc')->get();
    }

    public function findById($id)
    {
        return Airport::findOrFail($id);
    }

    public function create(array $data)
    {
        return Airport::create($data);
    }

    public function update($id, array $data)
    {
        $airport = Airport::findOrFail($id);

        $airport->update($data);

        return $airport;
    }

    public function delete($id)
    {
        return Airport::destroy($id);
    }
}