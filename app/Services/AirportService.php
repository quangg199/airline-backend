<?php

namespace App\Services;

use App\Repositories\Interfaces\AirportRepositoryInterface;

class AirportService
{
    protected $airportRepository;

    public function __construct(
        AirportRepositoryInterface $airportRepository
    ) {
        $this->airportRepository = $airportRepository;
    }

    public function getAllAirports()
    {
        return $this->airportRepository->getAll();
    }

    public function createAirport(array $data)
    {
        $data['code'] = strtoupper($data['code']);

        return $this->airportRepository->create($data);
    }

    public function updateAirport($id, array $data)
    {
        $data['code'] = strtoupper($data['code']);

        return $this->airportRepository->update($id, $data);
    }

    public function deleteAirport($id)
    {
        return $this->airportRepository->delete($id);
    }
}