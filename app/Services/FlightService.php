<?php

namespace App\Services;

use App\Repositories\FlightRepositoryInterface;

class FlightService
{
    protected $repo;

    public function __construct(FlightRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function getAllFlights()
    {
        return $this->repo->getAll();
    }

    public function createFlight($data)
    {
        // validate business rule
        if (isset($data['available_seats']) && $data['available_seats'] < 0) {
            throw new \Exception("Invalid seats");
        }

        return $this->repo->create($data);
    }

    public function updateFlight($id, $data)
    {
        return $this->repo->update($id, $data);
    }

    public function deleteFlight($id)
    {
        return $this->repo->delete($id);
    }
}