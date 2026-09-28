<?php

// 1. Target interface expected by the client
interface IReports
{
    // now takes the raw data string and returns JSON
    public function getJsonData(string $data): string;
}

// 2. Adaptee: provides XML data from a raw input
class XmlDataProvider
{
    // Expect data in "name:id" format (e.g. "Alice:42")
    public function getXmlData(string $data): string
    {
        $sep  = strpos($data, ':');
        $name = substr($data, 0, $sep);
        $id   = substr($data, $sep + 1);
        // Build an XML representation
        return "<user>"
            . "<name>" . $name . "</name>"
            . "<id>"   . $id   . "</id>"
            . "</user>";
    }
}

// 3. Adapter: implements IReports by converting XML -> JSON
class XmlDataProviderAdapter implements IReports
{
    private XmlDataProvider $xmlProvider;

    public function __construct(XmlDataProvider $provider)
    {
        $this->xmlProvider = $provider;
    }

    public function getJsonData(string $data): string
    {
        // 1. Get XML from the adaptee
        $xml = $this->xmlProvider->getXmlData($data);

        // 2. Naively parse out <name> and <id> values
        $startName = strpos($xml, "<name>") + 6;
        $endName   = strpos($xml, "</name>");
        $name      = substr($xml, $startName, $endName - $startName);

        $startId = strpos($xml, "<id>") + 4;
        $endId   = strpos($xml, "</id>");
        $id      = substr($xml, $startId, $endId - $startId);

        // 3. Build and return JSON
        return "{\"name\":\"" . $name . "\", \"id\":" . $id . "}";
    }
}

// 4. Client code works only with IReports
class Client
{
    public function getReport(IReports $report, string $rawData): void
    {
        echo "Processed JSON: " . $report->getJsonData($rawData) . PHP_EOL;
    }
}

class AdapterPattern
{
    public static function main(): void
    {
        // 1. Create the adaptee
        $xmlProv = new XmlDataProvider();

        // 2. Make our adapter
        /** @var IReports $adapter */
        $adapter = new XmlDataProviderAdapter($xmlProv);

        // 3. Give it some raw data
        $rawData = "Alice:42";

        // 4. Client prints the JSON
        $client = new Client();

        $client->getReport($adapter, $rawData);
        // -> Processed JSON: {"name":"Alice", "id":42}
    }
}

AdapterPattern::main();
