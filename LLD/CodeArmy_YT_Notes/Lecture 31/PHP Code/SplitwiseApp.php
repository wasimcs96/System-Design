<?php

// Helper equivalent to Java's new DecimalFormat("#.##").format(value)
// (max 2 decimals, no trailing zeros)
function df(float $value): string
{
    $s = number_format(round($value, 2), 2, '.', '');
    $s = rtrim(rtrim($s, '0'), '.');
    return ($s === '-0') ? '0' : $s;
}

enum SplitType
{
    case EQUAL;
    case EXACT;
    case PERCENTAGE;
}

class Split
{
    public string $userId;
    public float $amount;

    public function __construct(string $userId, float $amount)
    {
        $this->userId = $userId;
        $this->amount = $amount;
    }
}

// Observer Pattern - Notification interface
interface Observer
{
    public function update(string $message): void;
}

// Strategy Pattern - Split strategies
interface SplitStrategy
{
    /**
     * @param string[] $userIds
     * @param float[]  $values
     * @return Split[]
     */
    public function calculateSplit(float $totalAmount, array $userIds, array $values): array;
}

class EqualSplit implements SplitStrategy
{
    public function calculateSplit(float $totalAmount, array $userIds, array $values): array
    {
        $splits = [];
        $amountPerUser = $totalAmount / count($userIds);

        foreach ($userIds as $userId) {
            $splits[] = new Split($userId, $amountPerUser);
        }
        return $splits;
    }
}

class ExactSplit implements SplitStrategy
{
    public function calculateSplit(float $totalAmount, array $userIds, array $values): array
    {
        $splits = [];

        // validations

        for ($i = 0; $i < count($userIds); $i++) {
            $splits[] = new Split($userIds[$i], $values[$i]);
        }
        return $splits;
    }
}

class PercentageSplit implements SplitStrategy
{
    public function calculateSplit(float $totalAmount, array $userIds, array $values): array
    {
        $splits = [];

        // validations

        for ($i = 0; $i < count($userIds); $i++) {
            $amount = ($totalAmount * $values[$i]) / 100.0;
            $splits[] = new Split($userIds[$i], $amount);
        }
        return $splits;
    }
}

// Factory for split strategies
class SplitFactory
{
    public static function getSplitStrategy(SplitType $type): SplitStrategy
    {
        return match ($type) {
            SplitType::EQUAL      => new EqualSplit(),
            SplitType::EXACT      => new ExactSplit(),
            SplitType::PERCENTAGE => new PercentageSplit(),
        };
    }
}

// User class --> Concrete Observer
class User implements Observer
{
    public static int $nextUserId = 0;
    public string $userId;
    public string $name;
    public string $email;
    /** @var array<string,float> userId -> amount (positive = they owe you, negative = you owe them) */
    public array $balances;

    public function __construct(string $name, string $email)
    {
        $this->userId = "user" . (++self::$nextUserId);
        $this->name = $name;
        $this->email = $email;
        $this->balances = [];
    }

    public function update(string $message): void
    {
        echo "[NOTIFICATION to " . $this->name . "]: " . $message . PHP_EOL;
    }

    public function updateBalance(string $otherUserId, float $amount): void
    {
        $this->balances[$otherUserId] = ($this->balances[$otherUserId] ?? 0.0) + $amount;

        // Remove if balance becomes zero
        if (abs($this->balances[$otherUserId]) < 0.01) {
            unset($this->balances[$otherUserId]);
        }
    }

    public function getTotalOwed(): float
    {
        $total = 0;
        foreach ($this->balances as $value) {
            if ($value < 0) {
                $total += abs($value);
            }
        }
        return $total;
    }

    public function getTotalOwing(): float
    {
        $total = 0;
        foreach ($this->balances as $value) {
            if ($value > 0) {
                $total += $value;
            }
        }
        return $total;
    }
}

// Expense Model class
class Expense
{
    public static int $nextExpenseId = 0;
    public string $expenseId;
    public string $description;
    public float $totalAmount;
    public string $paidByUserId;
    /** @var Split[] */
    public array $splits;
    public string $groupId;

    // Java had two constructors (with and without group) -> optional parameter in PHP
    public function __construct(string $desc, float $amount, string $paidBy,
                                array $splits, string $group = "")
    {
        $this->expenseId = "expense" . (++self::$nextExpenseId);
        $this->description = $desc;
        $this->totalAmount = $amount;
        $this->paidByUserId = $paidBy;
        $this->splits = $splits;
        $this->groupId = $group;
    }
}

class DebtSimplifier
{
    /**
     * @param array<string, array<string,float>> $groupBalances
     * @return array<string, array<string,float>>
     */
    public static function simplifyDebts(array $groupBalances): array
    {
        // Calculate net amount for each person
        $netAmounts = [];

        // Initialize all users with 0
        foreach ($groupBalances as $userId => $_) {
            $netAmounts[$userId] = 0.0;
        }

        // Calculate net amounts
        // We only need to process each balance once (not twice)
        // If groupBalances[A][B] = 200, it means B owes A 200
        // So A should receive 200 (positive) and B should pay 200 (negative)
        foreach ($groupBalances as $creditorId => $balances) {
            foreach ($balances as $debtorId => $amount) {
                // Only process positive amounts to avoid double counting
                if ($amount > 0) {
                    $netAmounts[$creditorId] += $amount;  // creditor receives
                    $netAmounts[$debtorId]   -= $amount;  // debtor pays
                }
            }
        }

        // Divide users into creditors and debtors  (each entry is [userId, amount])
        $creditors = []; // those who should receive money
        $debtors = [];   // those who should pay money

        foreach ($netAmounts as $userId => $net) {
            if ($net > 0.01) { // creditor
                $creditors[] = [$userId, $net];
            } elseif ($net < -0.01) { // debtor
                $debtors[] = [$userId, -$net]; // store positive amount
            }
        }

        // Sort for better optimization (largest amounts first)
        usort($creditors, fn($a, $b) => $b[1] <=> $a[1]);
        usort($debtors, fn($a, $b) => $b[1] <=> $a[1]);

        // Create new simplified balance map
        $simplifiedBalances = [];

        // Initialize empty maps for all users
        foreach ($groupBalances as $userId => $_) {
            $simplifiedBalances[$userId] = [];
        }

        // Use greedy algorithm to minimize transactions
        $i = 0;
        $j = 0;
        while ($i < count($creditors) && $j < count($debtors)) {
            [$creditorId, $creditorAmount] = $creditors[$i];
            [$debtorId, $debtorAmount] = $debtors[$j];

            // Find the minimum amount to settle
            $settleAmount = min($creditorAmount, $debtorAmount);

            // Update simplified balances
            // debtorId owes creditorId the settleAmount
            $simplifiedBalances[$creditorId][$debtorId] = $settleAmount;
            $simplifiedBalances[$debtorId][$creditorId] = -$settleAmount;

            // Update remaining amounts
            $creditors[$i][1] -= $settleAmount;
            $debtors[$j][1] -= $settleAmount;

            // Move to next creditor or debtor if current one is settled
            if ($creditors[$i][1] < 0.01) {
                $i++;
            }
            if ($debtors[$j][1] < 0.01) {
                $j++;
            }
        }

        return $simplifiedBalances;
    }
}

// Group class --> Concrete Observable
class Group
{
    private function getUserByuserId(string $userId): ?User
    {
        $user = null;

        foreach ($this->members as $member) {
            if ($member->userId === $userId) {
                $user = $member;
            }
        }
        return $user;
    }

    public static int $nextGroupId = 0;
    public string $groupId;
    public string $name;
    /** @var User[] observers */
    public array $members;
    /** @var array<string, Expense> Group's own expense book */
    public array $groupExpenses;
    /** @var array<string, array<string,float>> memberId -> {otherMemberId -> balance} */
    public array $groupBalances;

    public function __construct(string $name)
    {
        $this->groupId = "group" . (++self::$nextGroupId);
        $this->name = $name;
        $this->members = [];
        $this->groupExpenses = [];
        $this->groupBalances = [];
    }

    public function addMember(User $user): void
    {
        $this->members[] = $user;

        // Initialize balance map for new member
        $this->groupBalances[$user->userId] = [];
        echo $user->name . " added to group " . $this->name . PHP_EOL;
    }

    public function removeMember(string $userId): bool
    {
        // Check if user can be removed or not
        if (!$this->canUserLeaveGroup($userId)) {
            echo PHP_EOL . "User not allowed to leave group without clearing expenses" . PHP_EOL;
            return false;
        }

        // Remove from observers
        $this->members = array_values(array_filter(
            $this->members,
            fn(User $user) => $user->userId !== $userId
        ));

        // Remove from group balances
        unset($this->groupBalances[$userId]);

        // Remove this user from other members' balance maps
        foreach ($this->groupBalances as $memberId => $_) {
            unset($this->groupBalances[$memberId][$userId]);
        }
        return true;
    }

    public function notifyMembers(string $message): void
    {
        foreach ($this->members as $observer) {
            $observer->update($message);
        }
    }

    public function isMember(string $userId): bool
    {
        return array_key_exists($userId, $this->groupBalances);
    }

    // Update balance within group
    public function updateGroupBalance(string $fromUserId, string $toUserId, float $amount): void
    {
        $this->groupBalances[$fromUserId][$toUserId] = ($this->groupBalances[$fromUserId][$toUserId] ?? 0.0) + $amount;
        $this->groupBalances[$toUserId][$fromUserId] = ($this->groupBalances[$toUserId][$fromUserId] ?? 0.0) - $amount;

        // Remove if balance becomes zero
        if (abs($this->groupBalances[$fromUserId][$toUserId]) < 0.01) {
            unset($this->groupBalances[$fromUserId][$toUserId]);
        }
        if (abs($this->groupBalances[$toUserId][$fromUserId]) < 0.01) {
            unset($this->groupBalances[$toUserId][$fromUserId]);
        }
    }

    // Check if user can leave group.
    public function canUserLeaveGroup(string $userId): bool
    {
        if (!$this->isMember($userId)) {
            throw new RuntimeException("user is not a part of this group");
        }

        // Check if user has any outstanding balance with other group members
        $userBalanceSheet = $this->groupBalances[$userId];
        foreach ($userBalanceSheet as $value) {
            if (abs($value) > 0.01) {
                return false; // Has outstanding balance
            }
        }
        return true;
    }

    // Get user's balance within this group
    /** @return array<string,float> */
    public function getUserGroupBalances(string $userId): array
    {
        if (!$this->isMember($userId)) {
            throw new RuntimeException("user is not a part of this group");
        }
        return $this->groupBalances[$userId];
    }

    // Add expense to this group  (Java overload without splitValues -> default [])
    /**
     * @param string[] $involvedUsers
     * @param float[]  $splitValues
     */
    public function addExpense(string $description, float $amount, string $paidByUserId,
                               array $involvedUsers, SplitType $splitType,
                               array $splitValues = []): bool
    {
        if (!$this->isMember($paidByUserId)) {
            throw new RuntimeException("user is not a part of this group");
        }

        // Validate that all involved users are group members
        foreach ($involvedUsers as $userId) {
            if (!$this->isMember($userId)) {
                throw new RuntimeException("involvedUsers are not a part of this group");
            }
        }

        // Generate splits using strategy pattern
        $splits = SplitFactory::getSplitStrategy($splitType)
            ->calculateSplit($amount, $involvedUsers, $splitValues);

        // Create expense in group's own expense book
        $expense = new Expense($description, $amount, $paidByUserId, $splits, $this->groupId);
        $this->groupExpenses[$expense->expenseId] = $expense;

        // Update group balances
        foreach ($splits as $split) {
            if ($split->userId !== $paidByUserId) {
                // Person who paid gets positive balance, person who owes gets negative
                $this->updateGroupBalance($paidByUserId, $split->userId, $split->amount);
            }
        }

        echo PHP_EOL . "=========== Sending Notifications ====================" . PHP_EOL;
        $paidByName = $this->getUserByuserId($paidByUserId)->name;
        $this->notifyMembers("New expense added: " . $description . " (Rs " . $amount . ")");

        // Printing console message-------
        echo PHP_EOL . "=========== Expense Message ====================" . PHP_EOL;
        echo "Expense added to " . $this->name . ": " . $description . " (Rs " . $amount
            . ") paid by " . $paidByName . " and involved people are : " . PHP_EOL;
        if (!empty($splitValues)) {
            for ($i = 0; $i < count($splitValues); $i++) {
                echo $this->getUserByuserId($involvedUsers[$i])->name . " : " . $splitValues[$i] . PHP_EOL;
            }
        } else {
            foreach ($involvedUsers as $user) {
                echo $this->getUserByuserId($user)->name . ", ";
            }
            echo PHP_EOL . "Will be Paid Equally" . PHP_EOL;
        }
        //-----------------------------------

        return true;
    }

    public function settlePayment(string $fromUserId, string $toUserId, float $amount): bool
    {
        // Validate that both users are group members
        if (!$this->isMember($fromUserId) || !$this->isMember($toUserId)) {
            echo "user is not a part of this group" . PHP_EOL;
            return false;
        }

        // Update group balances
        $this->updateGroupBalance($fromUserId, $toUserId, $amount);

        // Get user names for display
        $fromName = $this->getUserByuserId($fromUserId)->name;
        $toName = $this->getUserByuserId($toUserId)->name;

        // Notify group members
        $this->notifyMembers("Settlement: " . $fromName . " paid " . $toName . " Rs " . $amount);

        echo "Settlement in " . $this->name . ": " . $fromName . " settled Rs "
            . $amount . " with " . $toName . PHP_EOL;

        return true;
    }

    public function showGroupBalances(): void
    {
        echo PHP_EOL . "=== Group Balances for " . $this->name . " ===" . PHP_EOL;

        foreach ($this->groupBalances as $memberId => $userBalances) {
            $memberName = $this->getUserByuserId($memberId)->name;

            echo $memberName . "'s balances in group:" . PHP_EOL;

            if (empty($userBalances)) {
                echo "  No outstanding balances" . PHP_EOL;
            } else {
                foreach ($userBalances as $otherMemberUserId => $balance) {
                    $otherName = $this->getUserByuserId($otherMemberUserId)->name;

                    if ($balance > 0) {
                        echo "  " . $otherName . " owes: Rs " . df($balance) . PHP_EOL;
                    } else {
                        echo "  Owes " . $otherName . ": Rs " . df(abs($balance)) . PHP_EOL;
                    }
                }
            }
        }
    }

    public function simplifyGroupDebts(): void
    {
        $simplifiedBalances = DebtSimplifier::simplifyDebts($this->groupBalances);
        $this->groupBalances = $simplifiedBalances;

        echo PHP_EOL . "Debts have been simplified for group: " . $this->name . PHP_EOL;
    }
}

// Main ExpenseManager class (Singleton - Facade)
class Splitwise
{
    /** @var array<string, User> */
    private array $users;
    /** @var array<string, Group> */
    private array $groups;
    /** @var array<string, Expense> */
    private array $expenses;

    private static ?Splitwise $instance = null;

    private function __construct()
    {
        $this->users = [];
        $this->groups = [];
        $this->expenses = [];
    }

    public static function getInstance(): Splitwise
    {
        if (self::$instance === null) {
            self::$instance = new Splitwise();
        }
        return self::$instance;
    }

    // User management
    public function createUser(string $name, string $email): User
    {
        $user = new User($name, $email);
        $this->users[$user->userId] = $user;
        echo "User created: " . $name . " (ID: " . $user->userId . ")" . PHP_EOL;
        return $user;
    }

    public function getUser(string $userId): ?User
    {
        return $this->users[$userId] ?? null;
    }

    // Group management
    public function createGroup(string $name): Group
    {
        $group = new Group($name);
        $this->groups[$group->groupId] = $group;
        echo "Group created: " . $name . " (ID: " . $group->groupId . ")" . PHP_EOL;
        return $group;
    }

    public function getGroup(string $groupId): ?Group
    {
        return $this->groups[$groupId] ?? null;
    }

    public function addUserToGroup(string $userId, string $groupId): void
    {
        $user = $this->getUser($userId);
        $group = $this->getGroup($groupId);

        if ($user !== null && $group !== null) {
            $group->addMember($user);
        }
    }

    // Try to remove user from group - just delegates to group
    public function removeUserFromGroup(string $userId, string $groupId): bool
    {
        $group = $this->getGroup($groupId);

        if ($group === null) {
            echo "Group not found!" . PHP_EOL;
            return false;
        }

        $user = $this->getUser($userId);
        if ($user === null) {
            echo "User not found!" . PHP_EOL;
            return false;
        }

        $userRemoved = $group->removeMember($userId);

        if ($userRemoved) {
            echo $user->name . " successfully left " . $group->name . PHP_EOL;
        }
        return $userRemoved;
    }

    // Expense management - delegate to group
    /**
     * @param string[] $involvedUsers
     * @param float[]  $splitValues
     */
    public function addExpenseToGroup(string $groupId, string $description, float $amount,
                                      string $paidByUserId, array $involvedUsers,
                                      SplitType $splitType, array $splitValues = []): void
    {
        $group = $this->getGroup($groupId);
        if ($group === null) {
            echo "Group not found!" . PHP_EOL;
            return;
        }

        $group->addExpense($description, $amount, $paidByUserId, $involvedUsers, $splitType, $splitValues);
    }

    // Settlement - delegate to group
    public function settlePaymentInGroup(string $groupId, string $fromUserId,
                                         string $toUserId, float $amount): void
    {
        $group = $this->getGroup($groupId);
        if ($group === null) {
            echo "Group not found!" . PHP_EOL;
            return;
        }

        $group->settlePayment($fromUserId, $toUserId, $amount);
    }

    // Settlement
    public function settleIndividualPayment(string $fromUserId, string $toUserId, float $amount): void
    {
        $fromUser = $this->getUser($fromUserId);
        $toUser = $this->getUser($toUserId);

        if ($fromUser !== null && $toUser !== null) {
            $fromUser->updateBalance($toUserId, $amount);
            $toUser->updateBalance($fromUserId, -$amount);

            echo $fromUser->name . " settled Rs" . $amount . " with " . $toUser->name . PHP_EOL;
        }
    }

    /** @param float[] $splitValues */
    public function addIndividualExpense(string $description, float $amount, string $paidByUserId,
                                         string $toUserId, SplitType $splitType,
                                         array $splitValues = []): void
    {
        $strategy = SplitFactory::getSplitStrategy($splitType);
        $splits = $strategy->calculateSplit($amount, [$paidByUserId, $toUserId], $splitValues);

        $expense = new Expense($description, $amount, $paidByUserId, $splits);
        $this->expenses[$expense->expenseId] = $expense;

        $paidByUser = $this->getUser($paidByUserId);
        $toUser = $this->getUser($toUserId);

        $paidByUser->updateBalance($toUserId, $amount);
        $toUser->updateBalance($paidByUserId, -$amount);

        echo "Individual expense added: " . $description . " (Rs " . $amount
            . ") paid by " . $paidByUser->name . " for " . $toUser->name . PHP_EOL;
    }

    // Display Method
    public function showUserBalance(string $userId): void
    {
        $user = $this->getUser($userId);
        if ($user === null) return;

        echo PHP_EOL . "=========== Balance for " . $user->name . " ====================" . PHP_EOL;
        echo "Total you owe: Rs " . df($user->getTotalOwed()) . PHP_EOL;
        echo "Total others owe you: Rs " . df($user->getTotalOwing()) . PHP_EOL;

        echo "Detailed balances:" . PHP_EOL;
        foreach ($user->balances as $otherId => $value) {
            $otherUser = $this->getUser($otherId);
            if ($otherUser !== null) {
                if ($value > 0) {
                    echo "  " . $otherUser->name . " owes you: Rs" . $value . PHP_EOL;
                } else {
                    echo "  You owe " . $otherUser->name . ": Rs" . abs($value) . PHP_EOL;
                }
            }
        }
    }

    public function showGroupBalances(string $groupId): void
    {
        $group = $this->getGroup($groupId);
        if ($group === null) return;

        $group->showGroupBalances();
    }

    public function simplifyGroupDebts(string $groupId): void
    {
        $group = $this->getGroup($groupId);
        if ($group === null) return;

        // Use group's balance data for debt simplification
        $group->simplifyGroupDebts();
    }
}

class SplitwiseApp
{
    public static function main(): void
    {
        $manager = Splitwise::getInstance();

        echo PHP_EOL . "=========== Creating Users ====================" . PHP_EOL;
        $user1 = $manager->createUser("Aditya", "aditya@gmail.com");
        $user2 = $manager->createUser("Rohit", "rohit@gmail.com");
        $user3 = $manager->createUser("Manish", "manish@gmail.com");
        $user4 = $manager->createUser("Saurav", "saurav@gmail.com");

        echo PHP_EOL . "=========== Creating Group and Adding Members ====================" . PHP_EOL;
        $hostelGroup = $manager->createGroup("Hostel Expenses");
        $manager->addUserToGroup($user1->userId, $hostelGroup->groupId);
        $manager->addUserToGroup($user2->userId, $hostelGroup->groupId);
        $manager->addUserToGroup($user3->userId, $hostelGroup->groupId);
        $manager->addUserToGroup($user4->userId, $hostelGroup->groupId);

        echo PHP_EOL . "=========== Adding Expenses in group ====================" . PHP_EOL;
        $groupMembers = [$user1->userId, $user2->userId, $user3->userId, $user4->userId];
        $manager->addExpenseToGroup($hostelGroup->groupId, "Lunch", 800.0, $user1->userId, $groupMembers, SplitType::EQUAL);

        $dinnerMembers = [$user1->userId, $user3->userId, $user4->userId];
        $dinnerAmounts = [200.0, 300.0, 200.0];
        $manager->addExpenseToGroup($hostelGroup->groupId, "Dinner", 700.0, $user3->userId, $dinnerMembers,
            SplitType::EXACT, $dinnerAmounts);

        echo PHP_EOL . "=========== printing Group-Specific Balances ====================" . PHP_EOL;
        $manager->showGroupBalances($hostelGroup->groupId);

        echo PHP_EOL . "=========== Debt Simplification ====================" . PHP_EOL;
        $manager->simplifyGroupDebts($hostelGroup->groupId);

        echo PHP_EOL . "=========== printing Group-Specific Balances ====================" . PHP_EOL;
        $manager->showGroupBalances($hostelGroup->groupId);

        echo PHP_EOL . "=========== Adding Individual Expense ====================" . PHP_EOL;
        $manager->addIndividualExpense("Coffee", 40.0, $user2->userId, $user4->userId, SplitType::EQUAL);

        echo PHP_EOL . "=========== printing User Balances ====================" . PHP_EOL;
        $manager->showUserBalance($user1->userId);
        $manager->showUserBalance($user2->userId);
        $manager->showUserBalance($user3->userId);
        $manager->showUserBalance($user4->userId);

        echo PHP_EOL . "==========Attempting to remove Rohit from group==========" . PHP_EOL;
        $manager->removeUserFromGroup($user2->userId, $hostelGroup->groupId);

        echo PHP_EOL . "======== Making Settlement to Clear Rohit's Debt ==========" . PHP_EOL;
        $manager->settlePaymentInGroup($hostelGroup->groupId, $user2->userId, $user3->userId, 200.0);

        echo PHP_EOL . "======== Attempting to Remove Rohit Again ==========" . PHP_EOL;
        $manager->removeUserFromGroup($user2->userId, $hostelGroup->groupId);

        echo PHP_EOL . "=========== Updated Group Balances ====================" . PHP_EOL;
        $manager->showGroupBalances($hostelGroup->groupId);
    }
}

SplitwiseApp::main();
