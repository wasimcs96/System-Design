<?php

/**
 * refactor_factory-method.php
 * ---------------------------------------------------------------------------
 * Standalone companion code file for Factory-RefactoringGuru-Bilingual-Study.md.
 *
 * Merges the two runnable examples from that document into one file:
 *   1. RefactoringGuru\FactoryMethod\Logistics — the Transport/Logistics
 *      walkthrough (Truck/Ship, RoadLogistics/SeaLogistics) from the
 *      Problem/Solution sections.
 *   2. RefactoringGuru\FactoryMethod\CrossPlatformUi — the Dialog/Button
 *      walkthrough (WindowsDialog/WebDialog) from the Pseudocode section.
 *
 * Namespaces are used only to keep the two examples' classes from colliding;
 * nothing here was copied from refactoring.guru — all code is original,
 * written for this study.
 *
 * Run with: php refactor_factory-method.php
 * No framework dependency required.
 * ---------------------------------------------------------------------------
 */

declare(strict_types=1);

namespace RefactoringGuru\FactoryMethod\Logistics {

    interface Transport
    {
        public function deliver(): string;
    }

    class Truck implements Transport
    {
        public function deliver(): string
        {
            return "Delivering cargo by land, in a box on wheels.";
        }
    }

    class Ship implements Transport
    {
        public function deliver(): string
        {
            return "Delivering cargo by sea, in a container on a hull.";
        }
    }

    abstract class Logistics
    {
        // The factory method — every concrete Logistics subclass overrides this.
        abstract public function createTransport(): Transport;

        // Core business logic, written entirely against the Transport interface.
        public function planDelivery(): string
        {
            $transport = $this->createTransport();
            return "Planning delivery. " . $transport->deliver();
        }
    }

    class RoadLogistics extends Logistics
    {
        public function createTransport(): Transport
        {
            return new Truck();
        }
    }

    class SeaLogistics extends Logistics
    {
        public function createTransport(): Transport
        {
            return new Ship();
        }
    }

    function run(): void
    {
        echo "=== RefactoringGuru-style example 1: Logistics / Transport ===\n";

        // Client code — never references Truck or Ship directly.
        foreach ([new RoadLogistics(), new SeaLogistics()] as $logistics) {
            echo $logistics->planDelivery() . "\n";
        }
        echo "\n";
    }
}

namespace RefactoringGuru\FactoryMethod\CrossPlatformUi {

    interface Button
    {
        public function render(): string;
    }

    class WindowsButton implements Button
    {
        public function render(): string
        {
            return "Rendered a square, Windows-styled button.";
        }
    }

    class HtmlButton implements Button
    {
        public function render(): string
        {
            return "Rendered an HTML <button> element.";
        }
    }

    abstract class Dialog
    {
        // The factory method.
        abstract public function createButton(): Button;

        // Shared rendering logic — knows nothing about WHICH button it's using.
        public function render(): string
        {
            $button = $this->createButton();
            return "Rendering dialog window.\n" . $button->render();
        }
    }

    class WindowsDialog extends Dialog
    {
        public function createButton(): Button
        {
            return new WindowsButton();
        }
    }

    class WebDialog extends Dialog
    {
        public function createButton(): Button
        {
            return new HtmlButton();
        }
    }

    // Client picks the concrete Dialog subclass based on configuration —
    // everything downstream of that one decision stays platform-agnostic.
    function buildDialogForOs(string $os): Dialog
    {
        return match ($os) {
            'windows' => new WindowsDialog(),
            'web' => new WebDialog(),
            default => throw new \RuntimeException("Unknown operating system: {$os}"),
        };
    }

    function run(): void
    {
        echo "=== RefactoringGuru-style example 2: Cross-Platform Dialog ===\n";

        $dialog = buildDialogForOs('web');
        echo $dialog->render() . "\n\n";

        $dialog2 = buildDialogForOs('windows');
        echo $dialog2->render() . "\n";
    }
}

namespace {
    \RefactoringGuru\FactoryMethod\Logistics\run();
    \RefactoringGuru\FactoryMethod\CrossPlatformUi\run();

    echo "\n=== Both examples demonstrated. ===\n";
}
