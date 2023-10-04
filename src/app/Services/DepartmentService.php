<?php

namespace App\Services;

use App\Repositories\ExampleInterface;

class ExampleService
{
    protected ExampleInterface $exampleRepository;

    public function __construct(
        ExampleInterface $exampleRepository
    ) {
        $this->exampleRepository = $exampleRepository;
    }
}
