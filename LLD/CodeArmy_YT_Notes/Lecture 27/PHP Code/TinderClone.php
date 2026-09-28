<?php

// -------------------- Observer Pattern -------------------- //

// Observer Pattern: Interface for notification observers
interface NotificationObserver
{
    public function update(string $message): void;
}

// Concrete observer
class UserNotificationObserver implements NotificationObserver
{
    private string $userId;

    public function __construct(string $id)
    {
        $this->userId = $id;
    }

    public function update(string $message): void
    {
        echo "Notification for user " . $this->userId . ": " . $message . PHP_EOL;
    }
}

// Observable for Observer Pattern
class NotificationService
{
    /** @var array<string, NotificationObserver> */
    private array $observers;

    // Singleton Pattern
    private static ?NotificationService $instance = null;

    private function __construct()
    {
        $this->observers = [];
    }

    public static function getInstance(): NotificationService
    {
        if (self::$instance === null) {
            self::$instance = new NotificationService();
        }
        return self::$instance;
    }

    public function registerObserver(string $userId, NotificationObserver $observer): void
    {
        $this->observers[$userId] = $observer;
    }

    public function removeObserver(string $userId): void
    {
        unset($this->observers[$userId]);
    }

    public function notifyUser(string $userId, string $message): void
    {
        if (array_key_exists($userId, $this->observers)) {
            $this->observers[$userId]->update($message);
        }
    }

    public function notifyAll(string $message): void
    {
        foreach ($this->observers as $userId => $observer) {
            $observer->update($message);
        }
    }
}

// -------------------- Basic Models -------------------- //

// Gender enum
enum Gender
{
    case MALE;
    case FEMALE;
    case NON_BINARY;
    case OTHER;
}

// Location class
class Location
{
    private float $latitude;
    private float $longitude;

    // Java had Location() and Location(lat, lon); PHP uses default parameter values instead.
    public function __construct(float $lat = 0.0, float $lon = 0.0)
    {
        $this->latitude = $lat;
        $this->longitude = $lon;
    }

    public function getLatitude(): float
    {
        return $this->latitude;
    }

    public function getLongitude(): float
    {
        return $this->longitude;
    }

    public function setLatitude(float $lat): void
    {
        $this->latitude = $lat;
    }

    public function setLongitude(float $lon): void
    {
        $this->longitude = $lon;
    }

    // Calculate distance in kilometers between two locations using Haversine formula
    public function distanceInKm(Location $other): float
    {
        $earthRadiusKm = 6371.0;
        $dLat = ($other->latitude - $this->latitude) * M_PI / 180.0;
        $dLon = ($other->longitude - $this->longitude) * M_PI / 180.0;

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos($this->latitude * M_PI / 180.0) * cos($other->latitude * M_PI / 180.0) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadiusKm * $c;
    }
}

// Interest class
class Interest
{
    private string $name;
    private string $category;

    public function __construct(string $n = "", string $c = "")
    {
        $this->name = $n;
        $this->category = $c;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCategory(): string
    {
        return $this->category;
    }
}

// Preference class
class Preference
{
    /** @var Gender[] */
    private array $interestedIn;
    private int $minAge;
    private int $maxAge;
    private float $maxDistance; // in kilometers
    /** @var string[] */
    private array $interests;

    public function __construct()
    {
        $this->interestedIn = [];
        $this->interests = [];
        $this->minAge = 18;
        $this->maxAge = 100;
        $this->maxDistance = 100.0;
    }

    public function addGenderPreference(Gender $gender): void
    {
        $this->interestedIn[] = $gender;
    }

    public function removeGenderPreference(Gender $gender): void
    {
        $idx = array_search($gender, $this->interestedIn, true);
        if ($idx !== false) {
            array_splice($this->interestedIn, $idx, 1);
        }
    }

    public function setAgeRange(int $min, int $max): void
    {
        $this->minAge = $min;
        $this->maxAge = $max;
    }

    public function setMaxDistance(float $distance): void
    {
        $this->maxDistance = $distance;
    }

    public function addInterest(string $interest): void
    {
        $this->interests[] = $interest;
    }

    public function removeInterest(string $interest): void
    {
        $idx = array_search($interest, $this->interests, true);
        if ($idx !== false) {
            array_splice($this->interests, $idx, 1);
        }
    }

    public function isInterestedInGender(Gender $gender): bool
    {
        return in_array($gender, $this->interestedIn, true);
    }

    public function isAgeInRange(int $age): bool
    {
        return $age >= $this->minAge && $age <= $this->maxAge;
    }

    public function isDistanceAcceptable(float $distance): bool
    {
        return $distance <= $this->maxDistance;
    }

    /** @return string[] */
    public function getInterests(): array
    {
        return $this->interests;
    }

    /** @return Gender[] */
    public function getInterestedGenders(): array
    {
        return $this->interestedIn;
    }

    public function getMinAge(): int
    {
        return $this->minAge;
    }

    public function getMaxAge(): int
    {
        return $this->maxAge;
    }

    public function getMaxDistance(): float
    {
        return $this->maxDistance;
    }
}

// -------------------- Message System -------------------- //

// Message class
class Message
{
    private string $senderId;
    private string $content;
    private int $timestamp; // milliseconds since epoch (like Java's System.currentTimeMillis())

    public function __construct(string $sender, string $msg)
    {
        $this->senderId = $sender;
        $this->content = $msg;
        $this->timestamp = (int) floor(microtime(true) * 1000);
    }

    public function getSenderId(): string
    {
        return $this->senderId;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function getFormattedTime(): string
    {
        return date("Y-m-d H:i:s", intdiv($this->timestamp, 1000));
    }
}

// Chat room class
class ChatRoom
{
    private string $id;
    /** @var string[] */
    private array $participantIds;
    /** @var Message[] */
    private array $messages;

    public function __construct(string $roomId, string $user1Id, string $user2Id)
    {
        $this->id = $roomId;
        $this->participantIds = [];
        $this->participantIds[] = $user1Id;
        $this->participantIds[] = $user2Id;
        $this->messages = [];
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function addMessage(string $senderId, string $content): void
    {
        $msg = new Message($senderId, $content);
        $this->messages[] = $msg;
    }

    public function hasParticipant(string $userId): bool
    {
        return in_array($userId, $this->participantIds, true);
    }

    /** @return Message[] */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /** @return string[] */
    public function getParticipants(): array
    {
        return $this->participantIds;
    }

    public function displayChat(): void
    {
        echo "===== Chat Room: " . $this->id . " =====" . PHP_EOL;
        foreach ($this->messages as $msg) {
            echo "[" . $msg->getFormattedTime() . "] " . $msg->getSenderId() . ": " . $msg->getContent() . PHP_EOL;
        }
        echo "=========================" . PHP_EOL;
    }
}

// -------------------- Profile System -------------------- //

// Profile class
class UserProfile
{
    private string $name;
    private int $age;
    private Gender $gender;
    private ?string $bio = null;
    /** @var string[] */
    private array $photos;
    /** @var Interest[] */
    private array $interests;
    private Location $location;

    public function __construct()
    {
        $this->name = "";
        $this->age = 0;
        $this->gender = Gender::OTHER;
        $this->photos = [];
        $this->interests = [];
        $this->location = new Location();
    }

    public function setName(string $n): void
    {
        $this->name = $n;
    }

    public function setAge(int $a): void
    {
        $this->age = $a;
    }

    public function setGender(Gender $g): void
    {
        $this->gender = $g;
    }

    public function setBio(string $b): void
    {
        $this->bio = $b;
    }

    public function addPhoto(string $photoUrl): void
    {
        $this->photos[] = $photoUrl;
    }

    public function removePhoto(string $photoUrl): void
    {
        $idx = array_search($photoUrl, $this->photos, true);
        if ($idx !== false) {
            array_splice($this->photos, $idx, 1);
        }
    }

    public function addInterest(string $name, string $category): void
    {
        $interest = new Interest($name, $category);
        $this->interests[] = $interest;
    }

    public function removeInterest(string $name): void
    {
        // Java: interests.removeIf(i -> i.getName().equals(name));
        $this->interests = array_values(array_filter(
            $this->interests,
            fn(Interest $i) => $i->getName() !== $name
        ));
    }

    public function setLocation(Location $loc): void
    {
        $this->location = $loc;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAge(): int
    {
        return $this->age;
    }

    public function getGender(): Gender
    {
        return $this->gender;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    /** @return string[] */
    public function getPhotos(): array
    {
        return $this->photos;
    }

    /** @return Interest[] */
    public function getInterests(): array
    {
        return $this->interests;
    }

    public function getLocation(): Location
    {
        return $this->location;
    }

    public function display(): void
    {
        echo "===== Profile =====" . PHP_EOL;
        echo "Name: " . $this->name . PHP_EOL;
        echo "Age: " . $this->age . PHP_EOL;
        echo "Gender: ";
        switch ($this->gender) {
            case Gender::MALE:
                echo "Male";
                break;
            case Gender::FEMALE:
                echo "Female";
                break;
            case Gender::NON_BINARY:
                echo "Non-binary";
                break;
            case Gender::OTHER:
                echo "Other";
                break;
        }
        echo PHP_EOL;
        echo "Bio: " . $this->bio . PHP_EOL;
        echo "Photos: ";
        foreach ($this->photos as $photo) {
            echo $photo . ", ";
        }
        echo PHP_EOL;
        echo "Interests: ";
        foreach ($this->interests as $i) {
            echo $i->getName() . " (" . $i->getCategory() . "), ";
        }
        echo PHP_EOL;
        echo "Location: " . $this->location->getLatitude() . ", " . $this->location->getLongitude() . PHP_EOL;
        echo "===================" . PHP_EOL;
    }
}

// -------------------- User System -------------------- //

// Swipe action enum
enum SwipeAction
{
    case LEFT;  // Dislike
    case RIGHT; // Like
}

// User class
class User
{
    private string $id;
    private UserProfile $profile;
    private Preference $preference;
    /** @var array<string, SwipeAction> userId -> action */
    private array $swipeHistory;
    private NotificationObserver $notificationObserver;

    public function __construct(string $userId)
    {
        $this->id = $userId;
        $this->profile = new UserProfile();
        $this->preference = new Preference();
        $this->swipeHistory = [];
        $this->notificationObserver = new UserNotificationObserver($userId);
        NotificationService::getInstance()->registerObserver($userId, $this->notificationObserver);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getProfile(): UserProfile
    {
        return $this->profile;
    }

    public function getPreference(): Preference
    {
        return $this->preference;
    }

    public function swipe(string $otherUserId, SwipeAction $action): void
    {
        $this->swipeHistory[$otherUserId] = $action;
    }

    public function hasLiked(string $otherUserId): bool
    {
        return array_key_exists($otherUserId, $this->swipeHistory) && $this->swipeHistory[$otherUserId] === SwipeAction::RIGHT;
    }

    public function hasDisliked(string $otherUserId): bool
    {
        return array_key_exists($otherUserId, $this->swipeHistory) && $this->swipeHistory[$otherUserId] === SwipeAction::LEFT;
    }

    public function hasInteractedWith(string $otherUserId): bool
    {
        return array_key_exists($otherUserId, $this->swipeHistory);
    }

    public function displayProfile(): void  // Principle of least knowledge
    {
        $this->profile->display();
    }
}

// -------------------- Location Service -------------------- //

// Strategy Pattern: Location service strategy interface
interface LocationStrategy
{
    /**
     * @param User[] $allUsers
     * @return User[]
     */
    public function findNearbyUsers(Location $location, float $maxDistance, array $allUsers): array;
}

// Concrete strategy: Basic location strategy
class BasicLocationStrategy implements LocationStrategy
{
    public function findNearbyUsers(Location $location, float $maxDistance, array $allUsers): array
    {
        $nearbyUsers = [];
        foreach ($allUsers as $user) {
            $distance = $location->distanceInKm($user->getProfile()->getLocation());
            if ($distance <= $maxDistance) {
                $nearbyUsers[] = $user;
            }
        }
        return $nearbyUsers;
    }
}

// Location service with Strategy Pattern
class LocationService
{
    private LocationStrategy $strategy;

    // Singleton Pattern
    private static ?LocationService $instance = null;

    private function __construct()
    {
        $this->strategy = new BasicLocationStrategy();
    }

    public static function getInstance(): LocationService
    {
        if (self::$instance === null) {
            self::$instance = new LocationService();
        }
        return self::$instance;
    }

    public function setStrategy(LocationStrategy $newStrategy): void
    {
        $this->strategy = $newStrategy;
    }

    /**
     * @param User[] $allUsers
     * @return User[]
     */
    public function findNearbyUsers(Location $location, float $maxDistance, array $allUsers): array
    {
        return $this->strategy->findNearbyUsers($location, $maxDistance, $allUsers);
    }
}

// -------------------- Matching System -------------------- //

enum MatcherType
{
    case BASIC;
    case INTERESTS_BASED;
    case LOCATION_BASED;
}

// Matcher interface
interface Matcher
{
    public function calculateMatchScore(User $user1, User $user2): float;
}

// Concrete matcher: Basic matcher
class BasicMatcher implements Matcher
{
    public function calculateMatchScore(User $user1, User $user2): float
    {
        // Basic scoring, just check if preferences align
        $user1LikesUser2Gender = $user1->getPreference()->isInterestedInGender($user2->getProfile()->getGender());
        $user2LikesUser1Gender = $user2->getPreference()->isInterestedInGender($user1->getProfile()->getGender());

        if (!$user1LikesUser2Gender || !$user2LikesUser1Gender) {
            return 0.0;
        }

        // Check age preference
        $user1LikesUser2Age = $user1->getPreference()->isAgeInRange($user2->getProfile()->getAge());
        $user2LikesUser1Age = $user2->getPreference()->isAgeInRange($user1->getProfile()->getAge());

        if (!$user1LikesUser2Age || !$user2LikesUser1Age) {
            return 0.0;
        }

        // Check distance preference
        $distance = $user1->getProfile()->getLocation()->distanceInKm($user2->getProfile()->getLocation());
        $user1LikesUser2Distance = $user1->getPreference()->isDistanceAcceptable($distance);
        $user2LikesUser1Distance = $user2->getPreference()->isDistanceAcceptable($distance);

        if (!$user1LikesUser2Distance || !$user2LikesUser1Distance) {
            return 0.0;
        }

        // If all basic criteria match, return a base score
        return 0.5; // 50% match
    }
}

// Concrete matcher: Interests-based matcher
class InterestsBasedMatcher implements Matcher
{
    public function calculateMatchScore(User $user1, User $user2): float
    {
        // First, check basic compatibility
        $basicMatcher = new BasicMatcher();
        $baseScore = $basicMatcher->calculateMatchScore($user1, $user2);

        if ($baseScore == 0.0) {
            return 0.0; // No need to continue if basic criteria don't match
        }

        // Calculate score based on shared interests
        $user1InterestNames = [];
        foreach ($user1->getProfile()->getInterests() as $interest) {
            $user1InterestNames[] = $interest->getName();
        }

        $sharedInterests = 0;
        foreach ($user2->getProfile()->getInterests() as $interest) {
            if (in_array($interest->getName(), $user1InterestNames, true)) {
                $sharedInterests++;
            }
        }

        // Bonus score based on shared interests (up to 0.5 additional points)
        $maxInterests = max(count($user1->getProfile()->getInterests()), count($user2->getProfile()->getInterests()));
        $interestScore = $maxInterests > 0 ? 0.5 * ($sharedInterests / $maxInterests) : 0.0;

        return $baseScore + $interestScore;
    }
}

// Concrete matcher: Location-based matcher
class LocationBasedMatcher implements Matcher
{
    public function calculateMatchScore(User $user1, User $user2): float
    {
        // First, check basic compatibility
        $interestsMatcher = new InterestsBasedMatcher();
        $baseScore = $interestsMatcher->calculateMatchScore($user1, $user2);

        if ($baseScore == 0.0) {
            return 0.0; // No need to continue if basic criteria don't match
        }

        // Calculate score based on proximity
        $distance = $user1->getProfile()->getLocation()->distanceInKm($user2->getProfile()->getLocation());
        $maxDistance = min($user1->getPreference()->getMaxDistance(), $user2->getPreference()->getMaxDistance());

        // Closer is better, score decreases with distance (up to 0.2 additional points)
        $proximityScore = $maxDistance > 0 ? 0.2 * (1.0 - ($distance / $maxDistance)) : 0.0;

        return $baseScore + $proximityScore;
    }
}

// Factory Pattern: Matcher factory
class MatcherFactory
{
    public static function createMatcher(MatcherType $type): Matcher
    {
        return match ($type) {
            MatcherType::BASIC           => new BasicMatcher(),
            MatcherType::INTERESTS_BASED => new InterestsBasedMatcher(),
            MatcherType::LOCATION_BASED  => new LocationBasedMatcher(),
        };
    }
}

// -------------------- Dating App -------------------- //

// Facade Pattern: Dating app system
class DatingApp
{
    /** @var User[] */
    private array $users;
    /** @var ChatRoom[] */
    private array $chatRooms;
    private Matcher $matcher;

    // Singleton Pattern
    private static ?DatingApp $instance = null;

    private function __construct()
    {
        $this->users = [];
        $this->chatRooms = [];

        // Default to location-based matcher
        $this->matcher = MatcherFactory::createMatcher(MatcherType::LOCATION_BASED);
    }

    public static function getInstance(): DatingApp
    {
        if (self::$instance === null) {
            self::$instance = new DatingApp();
        }
        return self::$instance;
    }

    public function setMatcher(MatcherType $type): void
    {
        $this->matcher = MatcherFactory::createMatcher($type);
    }

    public function createUser(string $userId): User
    {
        $user = new User($userId);
        $this->users[] = $user;
        return $user;
    }

    public function getUserById(string $userId): ?User
    {
        foreach ($this->users as $user) {
            if ($user->getId() === $userId) {
                return $user;
            }
        }
        return null;
    }

    /** @return User[] */
    public function findNearbyUsers(string $userId, float $maxDistance): array
    {
        $user = $this->getUserById($userId);
        if ($user === null) {
            return [];
        }

        // Find users within maxDistance km
        $nearbyUsers = LocationService::getInstance()->findNearbyUsers(
            $user->getProfile()->getLocation(), $maxDistance, $this->users);

        // Filter out the user themselves
        $idx = array_search($user, $nearbyUsers, true);
        if ($idx !== false) {
            array_splice($nearbyUsers, $idx, 1);
        }

        // Filter out users that don't match preferences or have already been swiped
        $filteredUsers = [];
        foreach ($nearbyUsers as $otherUser) {
            // Skip users that have already been interacted with
            if (!$user->hasInteractedWith($otherUser->getId())) {

                // Calculate match score
                $score = $this->matcher->calculateMatchScore($user, $otherUser);

                // If score is above 0, they meet basic preference criteria
                if ($score > 0) {
                    $filteredUsers[] = $otherUser;
                }
            }
        }

        return $filteredUsers;
    }

    public function swipe(string $userId, string $targetUserId, SwipeAction $action): bool
    {
        $user = $this->getUserById($userId);
        $targetUser = $this->getUserById($targetUserId);

        if ($user === null || $targetUser === null) {
            echo "User not found." . PHP_EOL;
            return false;
        }

        $user->swipe($targetUserId, $action);

        // Check if it's a match
        if ($action === SwipeAction::RIGHT && $targetUser->hasLiked($userId)) {
            // It's a match!
            $chatRoomId = $userId . "_" . $targetUserId;
            $chatRoom = new ChatRoom($chatRoomId, $userId, $targetUserId);
            $this->chatRooms[] = $chatRoom;

            // Notify both users
            NotificationService::getInstance()->notifyUser($userId, "You have a new match with " . $targetUser->getProfile()->getName() . "!");
            NotificationService::getInstance()->notifyUser($targetUserId, "You have a new match with " . $user->getProfile()->getName() . "!");
            return true;
        }
        return false;
    }

    public function getChatRoom(string $user1Id, string $user2Id): ?ChatRoom
    {
        foreach ($this->chatRooms as $chatRoom) {
            if ($chatRoom->hasParticipant($user1Id) && $chatRoom->hasParticipant($user2Id)) {
                return $chatRoom;
            }
        }
        return null;
    }

    public function sendMessage(string $senderId, string $receiverId, string $content): void
    {
        $chatRoom = $this->getChatRoom($senderId, $receiverId);
        if ($chatRoom === null) {
            echo "No chat room found between these users." . PHP_EOL;
            return;
        }

        // Notify the receiver
        $chatRoom->addMessage($senderId, $content);
        NotificationService::getInstance()->notifyUser($receiverId, "New message from " . $this->getUserById($senderId)->getProfile()->getName());
    }

    public function displayUser(string $userId): void
    {
        $user = $this->getUserById($userId);
        if ($user === null) {
            echo "User not found." . PHP_EOL;
            return;
        }
        $user->displayProfile();
    }

    public function displayChatRoom(string $user1Id, string $user2Id): void
    {
        $chatRoom = $this->getChatRoom($user1Id, $user2Id);
        if ($chatRoom === null) {
            echo "No chat room found between these users." . PHP_EOL;
            return;
        }
        $chatRoom->displayChat();
    }
}

// -------------------- Main -------------------- //

class TinderClone
{
    public static function main(): void
    {
        // Get the dating app instance
        $app = DatingApp::getInstance();

        // Create users
        $user1 = $app->createUser("user1");
        $user2 = $app->createUser("user2");

        // Set user1 profile
        $profile1 = $user1->getProfile();
        $profile1->setName("Rohan");
        $profile1->setAge(28);
        $profile1->setGender(Gender::MALE);
        $profile1->setBio("I am a software developer");
        $profile1->addPhoto("rohan_photo1.jpg");
        $profile1->addInterest("Coding", "Programming");
        $profile1->addInterest("Travel", "Lifestyle");
        $profile1->addInterest("Music", "Entertainment");

        // Setup user1 preferences
        $pref1 = $user1->getPreference();
        $pref1->addGenderPreference(Gender::FEMALE);
        $pref1->setAgeRange(25, 30);
        $pref1->setMaxDistance(10.0);
        $pref1->addInterest("Coding");
        $pref1->addInterest("Travel");

        // Setup user2 profile
        $profile2 = $user2->getProfile();
        $profile2->setName("Neha");
        $profile2->setAge(27);
        $profile2->setGender(Gender::FEMALE);
        $profile2->setBio("Art teacher who loves painting and traveling.");
        $profile2->addPhoto("neha_photo1.jpg");
        $profile2->addInterest("Painting", "Art");
        $profile2->addInterest("Travel", "Lifestyle");
        $profile2->addInterest("Music", "Entertainment");

        // Setup user2 preferences
        $pref2 = $user2->getPreference();
        $pref2->addGenderPreference(Gender::MALE);
        $pref2->setAgeRange(27, 30);
        $pref2->setMaxDistance(15.0);
        $pref2->addInterest("Coding");
        $pref2->addInterest("Movies");

        // Set location for user1
        $location1 = new Location();
        $location1->setLatitude(1.01);
        $location1->setLongitude(1.02);
        $profile1->setLocation($location1);

        // Set location for user2 (Close to user1, within 5km)
        $location2 = new Location();
        $location2->setLatitude(1.03);
        $location2->setLongitude(1.04);
        $profile2->setLocation($location2);

        // Display user profiles
        echo "---- User Profiles ----" . PHP_EOL;
        $app->displayUser("user1");
        $app->displayUser("user2");

        // Find nearby users for user1 (within 5km)
        echo PHP_EOL . "---- Nearby Users for user1 (within 5km) ----" . PHP_EOL;
        $nearbyUsers = $app->findNearbyUsers("user1", 5.0);
        echo "Found " . count($nearbyUsers) . " nearby users" . PHP_EOL;
        foreach ($nearbyUsers as $user) {
            echo "- " . $user->getProfile()->getName() . " (" . $user->getId() . ")" . PHP_EOL;
        }

        // User1 swipes right on User2
        echo PHP_EOL . "---- Swipe Actions ----" . PHP_EOL;
        echo "User1 swipes right on User2" . PHP_EOL;
        $app->swipe("user1", "user2", SwipeAction::RIGHT);

        // User2 swipes right on User1 (creating a match)
        echo "User2 swipes right on User1" . PHP_EOL;
        $app->swipe("user2", "user1", SwipeAction::RIGHT);

        // Send messages in the chat room
        echo PHP_EOL . "---- Chat Room ----" . PHP_EOL;
        $app->sendMessage("user1", "user2", "Hi Neha, Kaise ho?");

        $app->sendMessage("user2", "user1", "Hi Rohan, Ma bdiya tum btao");

        // Display the chat room
        $app->displayChatRoom("user1", "user2");
    }
}

TinderClone::main();
