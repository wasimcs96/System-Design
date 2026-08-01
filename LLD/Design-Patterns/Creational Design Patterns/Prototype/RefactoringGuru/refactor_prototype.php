<?php

/**
 * refactor_prototype.php
 * ---------------------------------------------------------------------------
 * Standalone runnable file collecting every PHP code example from
 * Prototype-RefactoringGuru-Bilingual-Study.md, in the same order they
 * appear in that document. All code is original (see the sourcing note at
 * the top of that document) — this file just makes the examples runnable
 * independent of the markdown/PDF.
 *
 * Sections:
 *   1. Pseudocode section's PHP translation (Shape/Rectangle/Circle/Application)
 *   2. Conceptual Example (Prototype/ComponentWithBackReference)
 *   3. Real-World-Shaped Example (Page/Author)
 *
 * Run with: php refactor_prototype.php
 * ---------------------------------------------------------------------------
 */

declare(strict_types=1);

namespace RefactoringGuru\Prototype\PseudocodeDemo {

    echo "=== Section 1: Pseudocode section's PHP translation ===\n\n";

    // Base prototype. / बेस प्रोटोटाइप।
    abstract class Shape
    {
        public int $x;
        public int $y;
        public string $color;

        // A regular constructor. / एक सामान्य कंस्ट्रक्टर।
        public function __construct()
        {
        }

        // The prototype constructor: initializes a fresh object with
        // values copied from the existing ($source) object.
        protected function initFrom(Shape $source): void
        {
            $this->x = $source->x;
            $this->y = $source->y;
            $this->color = $source->color;
        }

        // Every subclass must implement its own clone.
        abstract public function clone(): Shape;
    }

    // Concrete prototype: Rectangle
    class Rectangle extends Shape
    {
        public int $width;
        public int $height;

        public static function fromSource(Rectangle $source): self
        {
            $instance = new self();
            $instance->initFrom($source); // copy parent's fields
            $instance->width = $source->width;
            $instance->height = $source->height;
            return $instance;
        }

        public function clone(): Shape
        {
            return self::fromSource($this);
        }
    }

    // Concrete prototype: Circle
    class Circle extends Shape
    {
        public int $radius;

        public static function fromSource(Circle $source): self
        {
            $instance = new self();
            $instance->initFrom($source);
            $instance->radius = $source->radius;
            return $instance;
        }

        public function clone(): Shape
        {
            return self::fromSource($this);
        }
    }

    // Client code
    final class Application
    {
        /** @var Shape[] */
        private array $shapes = [];

        public function __construct()
        {
            $circle = new Circle();
            $circle->x = 10;
            $circle->y = 10;
            $circle->radius = 20;
            $circle->color = "red";
            $this->shapes[] = $circle;

            $anotherCircle = $circle->clone();
            $this->shapes[] = $anotherCircle;
            // $anotherCircle contains an exact copy of $circle.

            $rectangle = new Rectangle();
            $rectangle->width = 10;
            $rectangle->height = 20;
            $rectangle->color = "blue";
            $this->shapes[] = $rectangle;
        }

        public function businessLogic(): void
        {
            $shapesCopy = [];

            // We don't know the exact concrete type of each $s — only that
            // it's a Shape. Polymorphism ensures the correct clone() runs.
            foreach ($this->shapes as $s) {
                $shapesCopy[] = $s->clone();
            }

            foreach ($shapesCopy as $i => $copy) {
                printf(
                    "Copy #%d: %s (x=%d, y=%d, color=%s)\n",
                    $i,
                    get_class($copy),
                    $copy->x,
                    $copy->y,
                    $copy->color
                );
            }
        }
    }

    (new Application())->businessLogic();
    echo "\n";
}

namespace RefactoringGuru\Prototype\Conceptual {

    echo "=== Section 2: Conceptual Example ===\n\n";

    /**
     * The example class that has cloning ability. We'll see how the values of
     * fields with different types are cloned.
     */
    class Prototype
    {
        public $primitive;
        public $component;
        public $circularReference;

        /**
         * PHP has built-in cloning support via the `clone` keyword. It works
         * automatically for primitive-typed fields. Fields containing objects
         * keep their original references after a shallow clone, so any field
         * that should be independent needs explicit handling in __clone().
         */
        public function __clone()
        {
            $this->component = clone $this->component;

            // Cloning an object that has a nested object with a back-reference
            // requires special treatment: after cloning, the nested object
            // should point back at the NEW clone, not the original.
            $this->circularReference = clone $this->circularReference;
            $this->circularReference->prototype = $this;
        }
    }

    class ComponentWithBackReference
    {
        public $prototype;

        /**
         * Note that the constructor does not run during cloning — any
         * complex constructor logic that must also happen on clone needs to
         * be repeated inside __clone().
         */
        public function __construct(Prototype $prototype)
        {
            $this->prototype = $prototype;
        }
    }

    /**
     * The client code.
     */
    function clientCode()
    {
        $p1 = new Prototype();
        $p1->primitive = 245;
        $p1->component = new \DateTime();
        $p1->circularReference = new ComponentWithBackReference($p1);

        $p2 = clone $p1;
        if ($p1->primitive === $p2->primitive) {
            echo "Primitive field values have been carried over to a clone. Yay!\n";
        } else {
            echo "Primitive field values have not been copied. Booo!\n";
        }
        if ($p1->component === $p2->component) {
            echo "Simple component has not been cloned. Booo!\n";
        } else {
            echo "Simple component has been cloned. Yay!\n";
        }

        if ($p1->circularReference === $p2->circularReference) {
            echo "Component with back reference has not been cloned. Booo!\n";
        } else {
            echo "Component with back reference has been cloned. Yay!\n";
        }

        if ($p1->circularReference->prototype === $p2->circularReference->prototype) {
            echo "Component with back reference is linked to original object. Booo!\n";
        } else {
            echo "Component with back reference is linked to the clone. Yay!\n";
        }
    }

    clientCode();
    echo "\n";
}

namespace RefactoringGuru\Prototype\RealWorld {

    echo "=== Section 3: Real-World-Shaped Example ===\n\n";

    /**
     * Prototype.
     */
    class Page
    {
        private $title;
        private $body;

        /** @var Author */
        private $author;

        private $comments = [];

        /** @var \DateTime */
        private $date;

        // +100 private fields, in a real system.

        public function __construct(string $title, string $body, Author $author)
        {
            $this->title = $title;
            $this->body = $body;
            $this->author = $author;
            $this->author->addToPage($this);
            $this->date = new \DateTime();
        }

        public function addComment(string $comment): void
        {
            $this->comments[] = $comment;
        }

        /**
         * You can control exactly what carries over to the cloned object.
         * Here, when a page is cloned: it gets a new "Copy of ..." title;
         * the author stays the same object (we just also register the new
         * page against that same author); comments are NOT carried over;
         * and a fresh date is attached to the clone.
         */
        public function __clone()
        {
            $this->title = "Copy of " . $this->title;
            $this->author->addToPage($this);
            $this->comments = [];
            $this->date = new \DateTime();
        }
    }

    class Author
    {
        private $name;

        /** @var Page[] */
        private $pages = [];

        public function __construct(string $name)
        {
            $this->name = $name;
        }

        public function addToPage(Page $page): void
        {
            $this->pages[] = $page;
        }
    }

    /**
     * The client code.
     */
    function clientCode()
    {
        $author = new Author("John Smith");
        $page = new Page("Tip of the day", "Keep calm and carry on.", $author);

        $page->addComment("Nice tip, thanks!");

        $draft = clone $page;
        echo "Dump of the clone. Note that the author is now referencing two objects.\n\n";
        print_r($draft);
    }

    clientCode();
}
