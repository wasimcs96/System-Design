<?php
abstract class SearchEngine{
    abstract public function search($query):string;
}

class GoogleSearchEngine extends SearchEngine{
    public function search($query):string{
        return "Result from Google: Your searched query";
    }
}

class BingSearchEngine extends SearchEngine{
    public function search($query):string{
        return "Result from Bing: Your searched query";
    }
}

class SearchEngineFactory{
    public static function createSearchEngine(string $serachEngineType):SearchEngine{
        switch ($serachEngineType){
            case 'google': {
               return  $searchEngine = new GoogleSearchEngine();
            }
            case 'bing':{
               return $searchEngine = new BingSearchEngine();
            }
            default: {
                throw new InvalidArgumentException("Invalid search engine type: $serachEngineType");
            }
        }
    }
} 

// $googleSeachEngine = SearchEngineFactory::createSearchEngine('google');
// echo $googleSeachEngine->search("Query");
// echo "\n";
// $bingSeachEngine = SearchEngineFactory::createSearchEngine('bing');
// echo $bingSeachEngine->search("Query");

//Another Complex Example
//Factory method is a creational design pattern which solves the problem of creating product objects without specifying their concrete classes.
interface SocilaNetworkConnector{
    public function login();
    public function logout();
    public function createPost();
}

class FacebookConnector implements SocilaNetworkConnector{
    private string $email;
    private string $password;
    public function __construct(string $email, string $password){
        $this->email=$email;
        $this->password=$password;
    }
    public function login(){
        echo "Facebook Login \n";
    }
    public function logout(){
        echo "Facebook Logout \n";
    }
    public function createPost(){
        echo "Create a post in Facebook \n";
    }
}

class TwitterConnector implements SocilaNetworkConnector{
    private string $email;
    private string $password;
    public function __construct(string $email, string $password){
        $this->email=$email;
        $this->password=$password;
    }
    public function login(){
        echo "Twitter Login \n";
    }
    public function logout(){
        echo "Twitter Logout \n";
    }
    public function createPost(){
        echo "Create a post in Twitter \n";
    }
}


abstract class SocialNetworkPoster{
    abstract public function getSociaNetwork():SocilaNetworkConnector;

    public function post(){
        $SocilaNetworkConnector = $this->getSociaNetwork();
        $SocilaNetworkConnector->login();
        $SocilaNetworkConnector->createPost();
        $SocilaNetworkConnector->logout();
    }
}

class FacebookPoster extends SocialNetworkPoster{
    private $email, $password;
    public function __construct(string $email, string $password){
        $this->email=$email;
        $this->password=$password;
    }

    public function getSociaNetwork():SocilaNetworkConnector{
        return  new FacebookConnector($this->email, $this->password);
    }
}

class TwitterPoster extends SocialNetworkPoster{
    private $email, $password;
    public function __construct(string $email, string $password){
        $this->email=$email;
        $this->password=$password;
    }

    public function getSociaNetwork():SocilaNetworkConnector{
        return  new TwitterConnector($this->email, $this->password);
    }
}

class Factory{
    public static function completeTask(SocialNetworkPoster $SocialNetworkPoster){
        $SocialNetworkPoster->post();
    }
}


$FacebookPoster = new FacebookPoster("email", "password");
$TwitterPoster = new TwitterPoster("email", "password");

Factory::completeTask($FacebookPoster);
Factory::completeTask($TwitterPoster);









//abstract class SocialNetwrokPoster



?>