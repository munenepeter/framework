<?php

namespace Tabel\Core\Mantle;

use Tabel\Models\User; 
use Tabel\Core\Mantle\Logger;
use Tabel\Core\Mantle\Session;

class Auth {

    public static function login(string $email, string $password) {

        $user = User::findBy('email', $email);
      
        if (!$user) {
            logger("Info", "Login: No account with {$email} email with {$password}");
            Session::make('_msg_error', "Wrong credentials, Please try again!");
            return false;
        }

        $user = $user[0];

        if (!password_verify($password, $user->password)) {
            logger("Info", "Login: Wrong Credentials; Email: {$email} Password: {$password}");
            Session::make('_msg_error', "Wrong credentials, Please try again!");
            return false;
        }

        logger("Info", "Login: Logged in {$email} with " . password_hash($password, PASSWORD_DEFAULT));

        Session::make('loggedIn', true);
        Session::make('user_id', $user->id);
        Session::make('email', $user->email);

        Session::make('_msg_success', "Successfull login");

        return true;
    }
    public static function logout(string $user) {

        Session::unset($user);
        Session::make('loggedIn', false);

        logger("Info", "Login: logged out $user");
        Session::destroy();
    }
}
