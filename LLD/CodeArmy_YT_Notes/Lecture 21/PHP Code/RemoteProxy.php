<?php

interface IDataService
{
    public function fetchData(): string;
}

class RealDataService implements IDataService
{
    public function __construct()
    {
        // Imagine this connects to a remote server or loads heavy resources.
        echo "[RealDataService] Initialized (simulating remote setup)" . PHP_EOL;
    }

    public function fetchData(): string
    {
        return "[RealDataService] Data from server";
    }
}

// Remote proxy
class DataServiceProxy implements IDataService
{
    private RealDataService $realService;

    public function __construct()
    {
        $this->realService = new RealDataService();
    }

    public function fetchData(): string
    {
        echo "[DataServiceProxy] Connecting to remote service..." . PHP_EOL;
        return $this->realService->fetchData();
    }
}

class RemoteProxy
{
    public static function main(): void
    {
        /** @var IDataService $dataService */
        $dataService = new DataServiceProxy();
        $dataService->fetchData();
    }
}

RemoteProxy::main();
