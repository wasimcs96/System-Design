<?php

// interface BucketCreation{
//     public function createBucket();
// }

// class AWS implements BucketCreation{
//     private string $publicKey;
//     private string $privateKey;
//     public static int $bucketID;

//     public function __construct(){
//         self::$bucketID = 0;
//     }

//     public function createBucket(){
//         echo 'Bucket has been created' .  PHP_EOL;
//         return ++self::$bucketID;
//     }
// }

// interface CloudService{
//     public function getBucket();
// }

// class AWSCloudServiceAdapter implements CloudService{
//     public BucketCreation $cloud;

//     public function __construct(BucketCreation $cloud){
//         $this->cloud = $cloud;
//     }

//     public function getBucket(){
//          return $this->cloud->createBucket();
//     }
// }

// class client{
//     function getNewBucket(CloudService $cloudService){
//         return $cloudService->getBucket();
//     }
// }


// $AWSService = new AWS();
// $AWSCloudServiceAdapter = new AWSCloudServiceAdapter($AWSService);

// $client = new Client();
// echo $client->getNewBucket($AWSCloudServiceAdapter);




/**
 * ============================================================
 * ADAPTER DESIGN PATTERN
 * ============================================================
 *
 * Scenario:
 *
 * Our application wants to work with cloud storage.
 *
 * The application expects this interface:
 *
 *      CloudStorage
 *          |
 *          +-- createBucket()
 *          +-- uploadFile()
 *
 * But the third-party AWS SDK has a different API:
 *
 *      AwsS3Client
 *          |
 *          +-- createBucket(array $params)
 *          +-- putObject(array $params)
 *
 * We cannot/should not modify the AWS SDK.
 *
 * So we create an Adapter:
 *
 *      AwsS3Adapter
 *
 * The Adapter converts our application's API into
 * the AWS SDK's API.
 */


/**
 * ============================================================
 * 1. TARGET
 * ============================================================
 *
 * This is the interface that OUR APPLICATION expects.
 *
 * The Client knows only about CloudStorage.
 *
 * It does NOT know about AWS.
 */

interface CloudStorage
{
    public function createBucket(string $name): string;

    public function uploadFile(
        string $bucket,
        string $file
    ): bool;
}


/**
 * ============================================================
 * 2. ADAPTEE
 * ============================================================
 *
 * Imagine this class comes from the AWS SDK.
 *
 * In a real application, this would be a third-party
 * library that we installed through Composer.
 *
 * IMPORTANT:
 *
 * We do NOT modify this class to implement CloudStorage.
 *
 * Its API is different from what our application expects.
 */

class AwsS3Client
{
    public function createBucket(array $params): array
    {
        echo "AWS: Creating bucket...\n";

        return [
            'Location' => $params['Bucket']
        ];
    }

    public function putObject(array $params): array
    {
        echo "AWS: Uploading file...\n";

        return [
            'status' => 200
        ];
    }
}


/**
 * ============================================================
 * 3. ADAPTER
 * ============================================================
 *
 * This is the most important class.
 *
 * AwsS3Adapter implements the interface expected by
 * our application.
 *
 * Internally it uses AwsS3Client.
 *
 * So:
 *
 * Application
 *      |
 *      | CloudStorage
 *      ↓
 * AwsS3Adapter
 *      |
 *      | AWS-specific API
 *      ↓
 * AwsS3Client
 */

class AwsS3Adapter implements CloudStorage
{
    /**
     * The actual AWS SDK object.
     *
     * We use composition here instead of inheritance.
     */
    private AwsS3Client $aws;

    public function __construct(AwsS3Client $aws)
    {
        $this->aws = $aws;
    }


    /**
     * Application calls:
     *
     *     createBucket("my-bucket")
     *
     * But AWS expects:
     *
     *     createBucket([
     *         'Bucket' => 'my-bucket'
     *     ])
     *
     * The Adapter converts one API into the other.
     */
    public function createBucket(string $name): string
    {
        $response = $this->aws->createBucket([
            'Bucket' => $name
        ]);

        return $response['Location'];
    }


    /**
     * Application calls:
     *
     *     uploadFile($bucket, $file)
     *
     * But AWS expects:
     *
     *     putObject([
     *         'Bucket'    => ...,
     *         'Key'       => ...,
     *         'SourceFile'=> ...
     *     ])
     *
     * Again, the Adapter translates the API.
     */
    public function uploadFile(
        string $bucket,
        string $file
    ): bool {

        $response = $this->aws->putObject([
            'Bucket'    => $bucket,
            'Key'       => basename($file),
            'SourceFile' => $file
        ]);

        return $response['status'] === 200;
    }
}


/**
 * ============================================================
 * 4. CLIENT
 * ============================================================
 *
 * The Client depends ONLY on CloudStorage.
 *
 * It does not know:
 *
 * - AWS
 * - AwsS3Client
 * - AWS API format
 * - AWS-specific parameters
 *
 * This is an important benefit of the Adapter Pattern.
 */

class FileStorageService
{
    private CloudStorage $storage;

    public function __construct(CloudStorage $storage)
    {
        $this->storage = $storage;
    }


    public function upload(string $bucketName, string $file): void
    {
        /**
         * Application uses our standard interface.
         */
        $bucket = $this->storage->createBucket($bucketName);

        echo "Bucket created: $bucket\n";


        /**
         * Application doesn't know that this will
         * eventually call AWS putObject().
         */
        $success = $this->storage->uploadFile(
            $bucket,
            $file
        );


        if ($success) {
            echo "File uploaded successfully.\n";
        } else {
            echo "File upload failed.\n";
        }
    }
}


/**
 * ============================================================
 * 5. APPLICATION
 * ============================================================
 */


/**
 * Create the third-party AWS client.
 */
$aws = new AwsS3Client();


/**
 * Wrap AWS inside our Adapter.
 *
 * AWS SDK
 *    ↓
 * AwsS3Adapter
 *    ↓
 * CloudStorage
 */
$storage = new AwsS3Adapter($aws);


/**
 * Give the Adapter to our application service.
 *
 * Notice:
 *
 * FileStorageService does not receive AwsS3Client.
 *
 * It receives CloudStorage.
 */
$fileStorage = new FileStorageService($storage);


/**
 * Application doesn't need to know anything about AWS.
 */
$fileStorage->upload(
    'my-application-bucket',
    '/tmp/profile.jpg'
);




