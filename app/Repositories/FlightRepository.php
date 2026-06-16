<?php

namespace App\Repositories;

use App\Models\Flight;

class FlightRepository implements FlightRepositoryInterface
{
    protected $model;

    public function __construct(Flight $model)
    {
        $this->model = $model;
    }

    public function getAll()
    {
        return $this->model->with([
            'departureAirport',
            'arrivalAirport',
            'aircraft'
        ])->get();
    }

    public function find($id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        $flight = $this->find($id);
        $flight->update($data);
        return $flight;
    }

    public function delete($id)
    {
        return $this->model->destroy($id);
    }
}