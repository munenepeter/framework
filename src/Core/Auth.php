<?php

namespace Tabel\Core;

use App\Models\User;
use Tabel\Modules\Session;

class Auth {
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_TIME = 900; // 15 minutes in seconds

    /**
     * Attempt to log in a user
     * 
     * @param string $email User email
     * @param string $password User password
     * @return bool
     */
    public static function login(string $email, string $password): bool {
        if (self::isLockedOut($email)) {
            Session::make('_msg_error', "Too many login attempts. Please try again later.");
            return false;
        }

        try {
            $user = User::where('email', $email)->first();
            
            if (!$user) {
                self::incrementLoginAttempts($email);
                logger("Warning", "Login attempt with non-existent email: {$email}");
                Session::make('_msg_error', "Invalid credentials");
                return false;
            }

            if (!password_verify($password, $user->password)) {
                self::incrementLoginAttempts($email);
                logger("Warning", "Failed login attempt for email: {$email}");
                Session::make('_msg_error', "Invalid credentials");
                return false;
            }

            // Success - clear any failed attempts
            self::clearLoginAttempts($email);

            // Set session data
            self::setUserSession($user);
            
            logger("Info", "Successful login for {$email}");
            Session::make('_msg_success', "Login successful");
            return true;

        } catch (\Exception $e) {
            logger("Error", "Login error: " . $e->getMessage());
            Session::make('_msg_error', "An error occurred during login");
            return false;
        }
    }

    /**
     * Log out the current user
     * 
     * @return User|null
     * @return void
     */
    public static function logout(User $user): void {
        try {
            // Clear session data
            Session::make('loggedIn', false);
            Session::make('user_id', $user->id);
            Session::make('email', $user->email);
            Session::make('last_activity', null);

            logger("Info", "User logged out: $user");
            Session::destroy();
        } catch (\Exception $e) {
            Session::make('_msg_error', "An error occurred during logout");
            logger("Error", "Logout error: " . $e->getMessage());
        }
    }

    /**
     * Check if a user is currently logged in
     * 
     * @return bool
     */
    public static function check(): bool {
        return Session::get('loggedIn') === true;
    }

    /**
     * Get the current authenticated user
     * 
     * @return User|null
     */
    public static function user(): ?User {
        if (!self::check()) {
            return null;
        }

        try {
            return User::find(Session::get('user_id'));
        } catch (\Exception $e) {
            logger("Error", "Error fetching authenticated user: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if the current user has a specific role
     * 
     * @param string|array $roles
     * @return bool
     */
    public static function hasRole(string|array $roles): bool {
        $user = self::user();
        if (!$user) {
            return false;
        }

        $roles = is_array($roles) ? $roles : [$roles];
        return in_array($user->role, $roles);
    }

    /**
     * Set session data for authenticated user
     * 
     * @param User $user
     * @return void
     */
    private static function setUserSession(User $user): void {
        Session::make('loggedIn', true);
        Session::make('user_id', $user->id);
        Session::make('email', $user->email);
        Session::make('last_activity', time());
        
        // Regenerate session ID for security
        session_regenerate_id(true);
    }

    /**
     * Check if an email is locked out due to too many attempts
     * 
     * @param string $email
     * @return bool
     */
    private static function isLockedOut(string $email): bool {
        $attempts = Session::get("login_attempts_{$email}", 0);
        $lastAttempt = Session::get("last_attempt_{$email}", 0);

        if ($attempts >= self::MAX_LOGIN_ATTEMPTS) {
            if (time() - $lastAttempt < self::LOCKOUT_TIME) {
                return true;
            }
            // Reset if lockout time has passed
            self::clearLoginAttempts($email);
        }
        return false;
    }

    /**
     * Increment failed login attempts for an email
     * 
     * @param string $email
     * @return void
     */
    private static function incrementLoginAttempts(string $email): void {
        $attempts = Session::get("login_attempts_{$email}", 0);
        Session::make("login_attempts_{$email}", $attempts + 1);
        Session::make("last_attempt_{$email}", time());
    }

    /**
     * Clear login attempts for an email
     * 
     * @param string $email
     * @return void
     */
    private static function clearLoginAttempts(string $email): void {
        Session::unset("login_attempts_{$email}");
        Session::unset("last_attempt_{$email}");
    }
}
