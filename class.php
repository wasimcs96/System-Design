<?php
class Developer{
    public string $name;
    public Team $team;

    public function __construct(string $name) {
        $this->name = $name;
    }

    public function assignTeam(Team $team){
        $this->team = $team;
    }

    public function getTeamName(): string {
        return $this->team->name;
    }
}

class Team{
    public string $name;
    public array $developers = [];

    public function __construct(string $name){
        $this->name = $name;
    }

    public function addDeveloper(Developer $developer){
        $this->developers[] = $developer;
        $developer->assignTeam($this);
    }
}

$dev1 = new Developer("Alice");
$dev2 = new Developer("Bob");

$team1 = new Team("Team A");
$team1->addDeveloper($dev1);
//$team1->addDeveloper($dev2);
echo $dev1->getTeamName();


?>