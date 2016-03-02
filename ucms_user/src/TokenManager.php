<?php


namespace MakinaCorpus\Ucms\User;

use Drupal\Core\Session\AccountInterface;

use MakinaCorpus\Ucms\User\Token;


/**
 * Management class for users tokens.
 */
final class TokenManager
{
    /**
     * @var \DatabaseConnection
     */
    private $db;


    /**
     * Default constructor
     *
     * @param \DatabaseConnection $db
     */
    public function __construct(\DatabaseConnection $db)
    {
        $this->db = $db;
    }


    /**
     * Creates and saves a new token for the given user.
     *
     * @param AccountInterface $user
     * @return Token
     */
    public function createToken(AccountInterface $user)
    {
        $lifespan = variable_get('user_password_reset_timeout', 86400);

        $token = new Token();
        $token->uid = $user->id();
        $token->expiration_date = (new \DateTime())->add(new \DateInterval('PT' . $lifespan . 'S'));
        $token->generateKey();
        $this->saveToken($token);

        return $token;
    }


    /**
     * Loads a token.
     *
     * @param string $key
     * @return Token
     */
    public function loadToken($key)
    {
        $token = $this->db
            ->select('ucms_user_token', 'ut')
            ->fields('ut')
            ->condition('token', $key)
            ->range(0, 1)
            ->execute()
            ->fetchObject('MakinaCorpus\\Ucms\\User\\Token');

        if (!$token) {
            return null;
        }

        $token->expiration_date = new \DateTime($token->expiration_date);
        return $token;
    }


    /**
     * Saves the given token.
     *
     * @param Token $token
     * @param int $lifespan
     *  Life duration in seconds.
     *  If not provided wa assume the expiration date is already defined.
     */
    public function saveToken(Token $token)
    {
        $this->db
            ->merge('ucms_user_token')
            ->key(['uid' => $token->uid])
            ->fields([
                'token' => $token->token,
                'expiration_date' => $token->expiration_date->format('Y-m-d H:i:s'),
            ])
            ->execute();
    }


    /**
     * Deletes the given token.
     *
     * @param Token $token
     */
    public function deleteToken(Token $token)
    {
        $this->db
            ->delete('ucms_user_token')
            ->condition('uid', $token->uid)
            ->execute();
    }


    /**
     * Deletes all expired tokens.
     */
    public function deleteExpiredTokens()
    {
        $this->db
            ->delete('ucms_user_token')
            ->condition('expiration_date', date('Y-m-d H:i:s'), '<')
            ->execute();
    }


    /**
     * Sends the asked mail type including a token generated for the given user.
     *
     * @global $language
     * @param AccountInterface $user
     *  The recipient of the mail
     * @param string $mailKey
     *  Key of the type of mail you want to send
     *
     * @see ucms_user_mail()
     */
    public function sendTokenMail(AccountInterface $user, $mailKey)
    {
        global $language;

        $token = $this->createToken($user);
        $params = ['user' => $user, 'token' => $token];

        drupal_mail('ucms_user', $mailKey, $user->getEmail(), $language, $params);
    }
}

