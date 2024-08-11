<?php
//Builder is a creational design pattern, which allows constructing complex objects step by step.

//Unlike other creational patterns, Builder doesn’t require products to have a common interface. That makes it possible to produce different products using the same construction process.

interface BuilderInterface{
    public function ProductPartA();
    public function ProductPartB();
    public function ProductPartC();
    public function ProductPartD();
}

class ProductBuilder implements BuilderInterface{
    public $product;

    function __construct(){
        $this->reset();
    }

    public function reset(){
        $this->product = new BaseProduct();
    }

    public function ProductPartA(){
        $this->product->partA[]= "PartA added into Product";
    }
    public function ProductPartB(){
        $this->product->partA[]= "PartB added into Product";
    }
    public function ProductPartC(){
        $this->product->partA[]= "PartC added into Product";
    }
    public function ProductPartD(){
        $this->product->partA[]= "PartD added into Product";
    }
    public function getFinalBuildProduct(){
        return $this->product;
    }
}

class BaseProduct{
    public $partA;
    public function getproduct() { 
        return $this->partA; 
    }
}

class Director{
    public $builder;
    public function __construct(BuilderInterface $build){
        $this->builder = $build;
    }

    function buildMinimalViableProduct(){
        $this->builder->ProductPartA();
        $this->builder->ProductPartB();
    }

    function buildAdvancedViableProduct(){
        $this->builder->ProductPartC();
        $this->builder->ProductPartD();
    }
}

$builderClass = new ProductBuilder();
$director = new Director($builderClass);

//setminimalProductfrom base product
$director->buildMinimalViableProduct();
print_r($director->builder->getFinalBuildProduct()->getproduct());

//setAdvanceProductfrom base product
$director->buildAdvancedViableProduct();
print_r($director->builder->getFinalBuildProduct()->getproduct());

//customize without director
$CustomBuilderClass = new ProductBuilder();
$CustomBuilderClass->ProductPartA();
$CustomBuilderClass->ProductPartC();
print_r($CustomBuilderClass->getFinalBuildProduct()->getproduct());


//Another Example
namespace RefactoringGuru\Builder\RealWorld;

/**
 * The Builder interface declares a set of methods to assemble an SQL query.
 *
 * All of the construction steps are returning the current builder object to
 * allow chaining: $builder->select(...)->where(...)
 */
interface SQLQueryBuilder
{
    public function select(string $table, array $fields): SQLQueryBuilder;

    public function where(string $field, string $value, string $operator = '='): SQLQueryBuilder;

    public function limit(int $start, int $offset): SQLQueryBuilder;

    // +100 other SQL syntax methods...

    public function getSQL(): string;
}

/**
 * Each Concrete Builder corresponds to a specific SQL dialect and may implement
 * the builder steps a little bit differently from the others.
 *
 * This Concrete Builder can build SQL queries compatible with MySQL.
 */
class MysqlQueryBuilder implements SQLQueryBuilder
{
    protected $query;

    protected function reset(): void
    {
        $this->query = new \stdClass();
    }

    /**
     * Build a base SELECT query.
     */
    public function select(string $table, array $fields): SQLQueryBuilder
    {
        $this->reset();
        $this->query->base = "SELECT " . implode(", ", $fields) . " FROM " . $table;
        $this->query->type = 'select';

        return $this;
    }

    /**
     * Add a WHERE condition.
     */
    public function where(string $field, string $value, string $operator = '='): SQLQueryBuilder
    {
        if (!in_array($this->query->type, ['select', 'update', 'delete'])) {
            throw new \Exception("WHERE can only be added to SELECT, UPDATE OR DELETE");
        }
        $this->query->where[] = "$field $operator '$value'";

        return $this;
    }

    /**
     * Add a LIMIT constraint.
     */
    public function limit(int $start, int $offset): SQLQueryBuilder
    {
        if (!in_array($this->query->type, ['select'])) {
            throw new \Exception("LIMIT can only be added to SELECT");
        }
        $this->query->limit = " LIMIT " . $start . ", " . $offset;

        return $this;
    }

    /**
     * Get the final query string.
     */
    public function getSQL(): string
    {
        $query = $this->query;
        $sql = $query->base;
        if (!empty($query->where)) {
            $sql .= " WHERE " . implode(' AND ', $query->where);
        }
        if (isset($query->limit)) {
            $sql .= $query->limit;
        }
        $sql .= ";";
        return $sql;
    }
}

/**
 * This Concrete Builder is compatible with PostgreSQL. While Postgres is very
 * similar to Mysql, it still has several differences. To reuse the common code,
 * we extend it from the MySQL builder, while overriding some of the building
 * steps.
 */
class PostgresQueryBuilder extends MysqlQueryBuilder
{
    /**
     * Among other things, PostgreSQL has slightly different LIMIT syntax.
     */
    public function limit(int $start, int $offset): SQLQueryBuilder
    {
        parent::limit($start, $offset);

        $this->query->limit = " LIMIT " . $start . " OFFSET " . $offset;

        return $this;
    }

    // + tons of other overrides...
}


/**
 * Note that the client code uses the builder object directly. A designated
 * Director class is not necessary in this case, because the client code needs
 * different queries almost every time, so the sequence of the construction
 * steps cannot be easily reused.
 *
 * Since all our query builders create products of the same type (which is a
 * string), we can interact with all builders using their common interface.
 * Later, if we implement a new Builder class, we will be able to pass its
 * instance to the existing client code without breaking it thanks to the
 * SQLQueryBuilder interface.
 */
function clientCode(SQLQueryBuilder $queryBuilder)
{
    // ...

    $query = $queryBuilder
        ->select("users", ["name", "email", "password"])
        ->where("age", 18, ">")
        ->where("age", 30, "<")
        ->limit(10, 20)
        ->getSQL();

    echo $query;

    // ...
}


/**
 * The application selects the proper query builder type depending on a current
 * configuration or the environment settings.
 */
// if ($_ENV['database_type'] == 'postgres') {
//     $builder = new PostgresQueryBuilder(); } else {
//     $builder = new MysqlQueryBuilder(); }
//
// clientCode($builder);


echo "Testing MySQL query builder:\n";
clientCode(new MysqlQueryBuilder());

echo "\n\n";

echo "Testing PostgresSQL query builder:\n";
clientCode(new PostgresQueryBuilder());



?>