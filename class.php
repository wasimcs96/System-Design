<?php

class User{
    public string $name;
    public string $email;
    public string $password;

    public function __construct($name, $email, $password){
        //
    }

    public function setPassowrd(){
        $this->passowrd = Hash($this->password);
    }
    
}


class UserRepositroy{
    public function save(User $user){
        //save user data into DB
    }
}

class UserNotification{


    public function sendNotification(User $user){
        //send Notification
    }
}


class UserService{
    public function __construct(private UserRepositroy $userRepo, private UserNotification $userNotification){}

    public function register($name, $email, $password){
        $user = new User($name, $email, $password);
        $this->userRepo->save($user);
    } 
}





