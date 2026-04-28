<?php
/**
 * B16. SPLITWISE (EXPENSE SPLITTER)
 * ============================================================
 * PROBLEM: Split expenses among groups of people, track balances,
 * and calculate minimal transactions to settle debts.
 *
 * PATTERNS:
 *  - Strategy : SplitStrategy (Equal, Exact, Percentage)
 * ============================================================
 */

// ─── Split Strategy ───────────────────────────────────────────
interface SplitStrategy {
    /**
     * Returns map of userId → amount they owe
     * @param string[] $participantIds
     */
    public function split(float $total, array $participantIds, array $extra = []): array;
}

class EqualSplit implements SplitStrategy {
    public function split(float $total, array $participantIds, array $extra = []): array {
        $share  = $total / count($participantIds);
        $result = [];
        foreach ($participantIds as $uid) $result[$uid] = round($share, 2);
        return $result;
    }
}

class ExactSplit implements SplitStrategy {
    /** $extra = ['uid' => amount, ...] */
    public function split(float $total, array $participantIds, array $extra = []): array {
        $sum = array_sum($extra);
        if (abs($sum - $total) > 0.01) throw new \InvalidArgumentException("Exact amounts don't sum to total");
        return $extra;
    }
}

class PercentageSplit implements SplitStrategy {
    /** $extra = ['uid' => percent, ...] */
    public function split(float $total, array $participantIds, array $extra = []): array {
        $sumPct = array_sum($extra);
        if (abs($sumPct - 100) > 0.01) throw new \InvalidArgumentException("Percentages must sum to 100");
        $result = [];
        foreach ($extra as $uid => $pct) $result[$uid] = round($total * $pct / 100, 2);
        return $result;
    }
}

// ─── Expense ──────────────────────────────────────────────────
class Expense {
    public readonly string $expenseId;
    /** @var array<string,float> userId → amount they owe */
    public readonly array  $shares;

    public function __construct(
        public readonly string         $paidBy,
        public readonly float          $amount,
        public readonly string         $description,
        array                          $participantIds,
        SplitStrategy                  $strategy,
        array                          $strategyData = []
    ) {
        $this->expenseId = uniqid('EXP-');
        $this->shares    = $strategy->split($amount, $participantIds, $strategyData);
    }
}

// ─── Balance Ledger ───────────────────────────────────────────
class BalanceLedger {
    /** @var array<string,array<string,float>> from → to → amount */
    private array $balances = [];

    public function recordExpense(Expense $expense): void {
        foreach ($expense->shares as $userId => $amount) {
            if ($userId === $expense->paidBy) continue; // Payer doesn't owe themselves
            // userId owes expense->paidBy amount
            $this->balances[$userId][$expense->paidBy] =
                ($this->balances[$userId][$expense->paidBy] ?? 0) + $amount;
        }
    }

    public function getBalance(string $from, string $to): float {
        $owes = $this->balances[$from][$to] ?? 0;
        $owed = $this->balances[$to][$from] ?? 0;
        return round($owes - $owed, 2); // Positive = $from owes $to
    }

    /** Greedy algorithm to minimize number of transactions */
    public function minimizeTransactions(array $userIds): array {
        // Build net balance for each user
        $net = [];
        foreach ($userIds as $uid) $net[$uid] = 0.0;
        foreach ($this->balances as $from => $tos) {
            foreach ($tos as $to => $amt) {
                $net[$from] -= $amt;
                $net[$to]   += $amt;
            }
        }

        // Separate creditors (positive) and debtors (negative)
        $transactions = [];
        $debtors   = array_filter($net, fn($v) => $v < -0.01);
        $creditors = array_filter($net, fn($v) => $v > 0.01);

        arsort($creditors); asort($debtors);
        $creditors = array_values($creditors); $creditorIds = array_keys(array_filter($net, fn($v) => $v > 0.01));
        $debtors   = array_values($debtors);   $debtorIds   = array_keys(array_filter($net, fn($v) => $v < -0.01));

        $i = 0; $j = 0;
        while ($i < count($debtors) && $j < count($creditors)) {
            $debtAmt   = abs($debtors[$i]);
            $creditAmt = $creditors[$j];
            $settled   = min($debtAmt, $creditAmt);
            $transactions[] = [
                'from'   => array_keys(array_filter($net, fn($v) => $v < -0.01))[$i] ?? '',
                'to'     => array_keys(array_filter($net, fn($v) => $v > 0.01))[$j] ?? '',
                'amount' => round($settled, 2),
            ];
            $debtors[$i]   += $settled;
            $creditors[$j] -= $settled;
            if (abs($debtors[$i]) < 0.01) $i++;
            if (abs($creditors[$j]) < 0.01) $j++;
        }
        return $transactions;
    }

    public function printSummary(array $userIds): void {
        foreach ($userIds as $from) {
            foreach ($userIds as $to) {
                if ($from === $to) continue;
                $bal = $this->getBalance($from, $to);
                if ($bal > 0) echo "  {$from} owes {$to}: ₹{$bal}\n";
            }
        }
    }
}

// ─── Group ────────────────────────────────────────────────────
class SplitGroup {
    /** @var string[] */
    private array $members  = [];
    private BalanceLedger $ledger;

    public function __construct(public readonly string $groupName) {
        $this->ledger = new BalanceLedger();
    }

    public function addMember(string $userId): void { $this->members[] = $userId; }

    public function addExpense(string $paidBy, float $amount, string $desc, SplitStrategy $strategy, array $data = []): void {
        $expense = new Expense($paidBy, $amount, $desc, $this->members, $strategy, $data);
        $this->ledger->recordExpense($expense);
        echo "  ✓ Expense: {$desc} ₹{$amount} paid by {$paidBy}\n";
    }

    public function showBalances(): void {
        echo "  --- Balances ---\n";
        $this->ledger->printSummary($this->members);
    }

    public function settle(): void {
        echo "  --- Minimum Transactions ---\n";
        $txns = $this->ledger->minimizeTransactions($this->members);
        foreach ($txns as $t) echo "  {$t['from']} → {$t['to']}: ₹{$t['amount']}\n";
    }
}

// ─── DRIVER CODE ──────────────────────────────────────────────
echo "=== B16. Splitwise ===\n\n";

$group = new SplitGroup('Goa Trip');
$group->addMember('Alice');
$group->addMember('Bob');
$group->addMember('Charlie');

$group->addExpense('Alice', 3000, 'Hotel', new EqualSplit());
$group->addExpense('Bob', 600, 'Dinner', new EqualSplit());
$group->addExpense('Charlie', 1200, 'Cab',
    new ExactSplit(), ['Alice' => 600, 'Bob' => 400, 'Charlie' => 200]);

$group->showBalances();
$group->settle();
