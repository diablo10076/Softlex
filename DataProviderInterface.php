<?php

declare(strict_types=1);

namespace src\Integration;

interface DataProviderInterface
{
    /**
     * @param array $request key=>value data
     * @return array
     */
    public function get(array $request): array ;
}
