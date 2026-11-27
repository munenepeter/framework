<?php

namespace App\Models {

    use Tabel\Database\Model;
    use Tabel\Database\QueryBuilder;

    /** Stand-in for the application's User model (Auth hard-codes App\Models\User). */
    class User extends Model {
        public static ?\PDO $pdo = null;
        protected static string $table = 'users';
        protected static bool $timestamps = true;
        protected static array $fillable = ['username', 'email', 'password', 'role'];

        protected static function qb(): QueryBuilder {
            return QueryBuilder::getInstance(self::$pdo);
        }
    }
}

namespace Tabel\Tests\Core {

    use App\Models\User;
    use PHPUnit\Framework\TestCase;
    use Tabel\Core\Auth;
    use Tabel\Database\QueryBuilder;

    class AuthTest extends TestCase {

        protected function setUp(): void {
            $_SESSION = [];
            User::$pdo = new \PDO('sqlite::memory:');
            User::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            User::$pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
            User::$pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, email TEXT UNIQUE, password TEXT, role TEXT, created_at TEXT, updated_at TEXT)');

            $ref = new \ReflectionClass(QueryBuilder::class);
            $p = $ref->getProperty('instance');
            $p->setAccessible(true);
            $p->setValue(null, null);

            User::create(['username' => 'Ann', 'email' => 'ann@example.com', 'password' => password_hash('secret123', PASSWORD_DEFAULT), 'role' => 'admin']);
        }

        public function testLoginWithCorrectPasswordSucceeds(): void {
            $this->assertTrue(Auth::login('ann@example.com', 'secret123'));
            $this->assertTrue(Auth::check());
            $this->assertSame('ann@example.com', $_SESSION['email']);
            $this->assertSame('Ann', Auth::user()->username);
            $this->assertTrue(Auth::hasRole('admin'));
        }

        public function testWrongPasswordAndUnknownEmailFail(): void {
            $this->assertFalse(Auth::login('ann@example.com', 'nope'));
            $this->assertFalse(Auth::login('ghost@example.com', 'secret123'));
            $this->assertFalse(Auth::check());
        }

        public function testErrorMessageIsReplacedOnTheNextFailure(): void {
            Auth::login('ghost@example.com', 'x');
            $_SESSION['_msg_error'] = 'stale';
            Auth::login('ghost@example.com', 'x');
            $this->assertSame('Invalid credentials', $_SESSION['_msg_error']);
        }

        public function testLockoutAfterFiveFailures(): void {
            for ($i = 0; $i < 5; $i++) {
                Auth::login('ann@example.com', 'bad');
            }
            $this->assertFalse(Auth::login('ann@example.com', 'secret123'));
            $this->assertStringContainsString('Too many', $_SESSION['_msg_error']);
        }

        public function testLogoutClearsTheSession(): void {
            Auth::login('ann@example.com', 'secret123');
            Auth::logout(Auth::user());
            $this->assertFalse(Auth::check());
            $this->assertSame([], $_SESSION);
        }
    }
}
