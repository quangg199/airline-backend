<?php

namespace App\Repositories;

interface BookingRepositoryInterface
{
    public function getAll();

    public function findById(int $id);

    public function delete(int $id);
}