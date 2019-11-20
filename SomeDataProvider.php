<?php

declare(strict_types=1);

namespace src\Integration;

class SomeDataProvider implements DataProviderInterface
{
    /**
     * @var Credentials
     */
    protected $credentials;

    /**
     * DataProvider constructor.
     * @param Credentials $credentials
     */
    public function __construct(Credentials $credentials)
    {
        $this->credentials = $credentials;
    }


    /**
     * @inheritdoc
     */
    public function get(array $request) : array
    {
        // returns a response from external service
    }
}
