<?php

/**
 * algomaster_factory-method.php
 * ---------------------------------------------------------------------------
 * Standalone companion code file for Factory-AlgoMaster-Bilingual-Study.md.
 *
 * Merges the two runnable examples from that document into one file:
 *   1. AlgoMaster\FactoryMethod\Notifications — the Notification/NotificationCreator
 *      walkthrough (Product, ConcreteProduct, Creator, ConcreteCreator, client
 *      code, and adding WhatsApp without touching existing code).
 *   2. AlgoMaster\FactoryMethod\DocumentExport — the "Practical Example:
 *      Document Export System" (PDF/HTML/CSV exporters).
 *
 * Namespaces are used only to keep the two examples' classes from colliding
 * (both define an interface-plus-Creator-hierarchy shape); nothing here was
 * copied from algomaster.io — all code is original, written for this study.
 *
 * Run with: php algomaster_factory-method.php
 * No framework dependency required.
 * ---------------------------------------------------------------------------
 */

declare(strict_types=1);

namespace AlgoMaster\FactoryMethod\Notifications {

    interface Notification
    {
        public function send(string $message): string;
    }

    class EmailNotification implements Notification
    {
        public function send(string $message): string
        {
            return "Emailed via SMTP: {$message}";
        }
    }

    class SmsNotification implements Notification
    {
        public function send(string $message): string
        {
            return "Texted via telephony API: {$message}";
        }
    }

    class WhatsAppNotification implements Notification
    {
        public function send(string $message): string
        {
            return "WhatsApp message sent: {$message}";
        }
    }

    abstract class NotificationCreator
    {
        // The factory method — every ConcreteCreator overrides this.
        abstract public function createNotification(): Notification;

        // Shared workflow: doesn't know WHAT it's sending, only HOW to send it.
        public function send(string $message): string
        {
            $notification = $this->createNotification();
            return $notification->send($message);
        }
    }

    class EmailNotificationCreator extends NotificationCreator
    {
        public function createNotification(): Notification
        {
            return new EmailNotification();
        }
    }

    class SmsNotificationCreator extends NotificationCreator
    {
        public function createNotification(): Notification
        {
            return new SmsNotification();
        }
    }

    // Step 6 — added later, with zero changes to anything above it.
    class WhatsAppNotificationCreator extends NotificationCreator
    {
        public function createNotification(): Notification
        {
            return new WhatsAppNotification();
        }
    }

    function run(): void
    {
        echo "=== AlgoMaster-style example 1: Notification Factory Method ===\n";

        $creators = [
            new EmailNotificationCreator(),
            new SmsNotificationCreator(),
        ];

        foreach ($creators as $creator) {
            echo $creator->send("Welcome!") . "\n";
        }

        // Adding a new channel afterward, touching nothing above.
        echo (new WhatsAppNotificationCreator())->send("Your order is confirmed") . "\n";
        echo "\n";
    }
}

namespace AlgoMaster\FactoryMethod\DocumentExport {

    interface ExportableDocument
    {
        public function render(array $rows): string;
    }

    class PdfDocument implements ExportableDocument
    {
        public function render(array $rows): string
        {
            return "[PDF] " . count($rows) . " row(s) rendered with PDF headers/footers.";
        }
    }

    class HtmlDocument implements ExportableDocument
    {
        public function render(array $rows): string
        {
            return "[HTML] <table>" . count($rows) . " row(s)</table>";
        }
    }

    class CsvDocument implements ExportableDocument
    {
        public function render(array $rows): string
        {
            return "[CSV] " . implode(",", array_keys($rows[0] ?? [])) . " + " . count($rows) . " row(s)";
        }
    }

    abstract class DocumentExporter
    {
        abstract public function createDocument(): ExportableDocument;

        // Shared sequence: header, rows, footer — defined once, here.
        public function export(array $rows): string
        {
            $document = $this->createDocument();
            return "Export starting...\n" . $document->render($rows) . "\nExport complete.";
        }
    }

    class PdfExporter extends DocumentExporter
    {
        public function createDocument(): ExportableDocument
        {
            return new PdfDocument();
        }
    }

    class HtmlExporter extends DocumentExporter
    {
        public function createDocument(): ExportableDocument
        {
            return new HtmlDocument();
        }
    }

    class CsvExporter extends DocumentExporter
    {
        public function createDocument(): ExportableDocument
        {
            return new CsvDocument();
        }
    }

    function run(): void
    {
        echo "=== AlgoMaster-style example 2: Document Export System ===\n";

        $rows = [
            ['name' => 'Amit', 'total' => 500],
            ['name' => 'Priya', 'total' => 750],
        ];

        foreach ([new PdfExporter(), new HtmlExporter(), new CsvExporter()] as $exporter) {
            echo $exporter->export($rows) . "\n\n";
        }
    }
}

namespace {
    \AlgoMaster\FactoryMethod\Notifications\run();
    \AlgoMaster\FactoryMethod\DocumentExport\run();

    echo "=== Both examples demonstrated. ===\n";
}
