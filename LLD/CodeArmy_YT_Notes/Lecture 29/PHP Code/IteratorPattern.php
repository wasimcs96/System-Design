<?php

/*
NOTE (PHP):
 - PHP already has a built-in interface called "Iterator", and "iterable" is a
   reserved type name. To define our OWN Iterator (like the Java lecture), we put
   the code in a namespace, and name the aggregate interface "IterableCollection".
 - PHP has no generics, so Iterator<T> becomes a plain interface with mixed return.
 - In real PHP code you would usually implement PHP's built-in \Iterator or
   \IteratorAggregate so that objects work directly in foreach loops.
*/

namespace IteratorPattern;

interface Iterator
{
    public function hasNext(): bool;
    public function next(): mixed;
}

interface IterableCollection
{
    public function getIterator(): Iterator;
}

// Linked List
class LinkedList implements IterableCollection
{
    public int $data;
    public ?LinkedList $next;

    public function __construct(int $value)
    {
        $this->data = $value;
        $this->next = null;
    }

    public function getIterator(): Iterator
    {
        return new LinkedListIterator($this);
    }
}

// Binary Tree
class BinaryTree implements IterableCollection
{
    public int $data;
    public ?BinaryTree $left;
    public ?BinaryTree $right;

    public function __construct(int $value)
    {
        $this->data = $value;
        $this->left = null;
        $this->right = null;
    }

    public function getIterator(): Iterator
    {
        return new BinaryTreeInorderIterator($this);
    }
}

// Song and Playlist
class Song
{
    public string $title;
    public string $artist;

    public function __construct(string $t, string $a)
    {
        $this->title = $t;
        $this->artist = $a;
    }
}

class Playlist implements IterableCollection
{
    /** @var Song[] */
    public array $songs = [];

    public function addSong(Song $s): void
    {
        $this->songs[] = $s;
    }

    public function getIterator(): Iterator
    {
        return new PlaylistIterator($this->songs);
    }
}

// Concrete Iterators

class LinkedListIterator implements Iterator
{
    private ?LinkedList $current;

    public function __construct(LinkedList $head)
    {
        $this->current = $head;
    }

    public function hasNext(): bool
    {
        return $this->current !== null;
    }

    public function next(): int
    {
        $val = $this->current->data;
        $this->current = $this->current->next;
        return $val;
    }
}

class BinaryTreeInorderIterator implements Iterator
{
    /** @var BinaryTree[] used as a stack */
    private array $stk = [];

    private function pushLefts(?BinaryTree $node): void
    {
        while ($node !== null) {
            $this->stk[] = $node; // push
            $node = $node->left;
        }
    }

    public function __construct(BinaryTree $root)
    {
        $this->pushLefts($root);
    }

    public function hasNext(): bool
    {
        return !empty($this->stk);
    }

    public function next(): int
    {
        $node = array_pop($this->stk);
        $val = $node->data;
        if ($node->right !== null) {
            $this->pushLefts($node->right);
        }
        return $val;
    }
}

class PlaylistIterator implements Iterator
{
    /** @var Song[] */
    private array $vec;
    private int $index = 0;

    /** @param Song[] $v */
    public function __construct(array $v)
    {
        $this->vec = $v;
    }

    public function hasNext(): bool
    {
        return $this->index < count($this->vec);
    }

    public function next(): Song
    {
        return $this->vec[$this->index++];
    }
}

// Main
class IteratorPatternDemo
{
    public static function main(): void
    {
        //------------------------------------------------
        // LinkedList: 1 -> 2 -> 3
        $list = new LinkedList(1);
        $list->next = new LinkedList(2);
        $list->next->next = new LinkedList(3);

        $iterator1 = $list->getIterator();

        echo "LinkedList contents: ";
        while ($iterator1->hasNext()) {
            echo $iterator1->next() . " ";
        }
        echo PHP_EOL;

        //------------------------------------------------

        // BinaryTree:
        //    2
        //   / \
        //  1   3
        $root = new BinaryTree(2);
        $root->left  = new BinaryTree(1);
        $root->right = new BinaryTree(3);

        $iterator2 = $root->getIterator();

        echo "BinaryTree inorder: ";
        while ($iterator2->hasNext()) {
            echo $iterator2->next() . " ";
        }
        echo PHP_EOL;

        //------------------------------------------------

        // Playlist
        $playlist = new Playlist();
        $playlist->addSong(new Song("Admirin You", "Karan Aujla"));
        $playlist->addSong(new Song("Husn", "Anuv Jain"));

        $iterator3 = $playlist->getIterator();

        echo "Playlist songs:" . PHP_EOL;
        while ($iterator3->hasNext()) {
            $s = $iterator3->next();
            echo "  " . $s->title . " by " . $s->artist . PHP_EOL;
        }
    }
}

IteratorPatternDemo::main();
