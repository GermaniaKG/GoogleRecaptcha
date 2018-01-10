<?php
namespace Germania\GoogleRecaptcha;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use ReCaptcha\ReCaptcha;

class GoogleRecaptchaCallable implements LoggerAwareInterface
{
    use LoggerAwareTrait;


    /**
     * @var ReCaptcha
     */
    public $recaptcha;


    /**
     * @param ReCaptcha            $recaptcha
     * @param LoggerInterface|null $logger
     */
    public function __construct( ReCaptcha $recaptcha, LoggerInterface $logger = null )
    {
        $this->recaptcha = $recaptcha;
        $this->setLogger( $logger ?: new NullLogger );
    }


    /**
     * @param  string $g_recaptcha_response
     * @param  string $remote_ip
     * @return bool
     *
     * See also: https://developers.google.com/recaptcha/docs/verify
     */
    public function __invoke( $g_recaptcha_response, $remote_ip)
    {
        $response = $this->recaptcha->verify($g_recaptcha_response, $remote_ip);


        if ($response->isSuccess()) :
            $this->logger->info( "Validation successful", [
                'remote_ip' => $remote_ip
            ]);
            return true;
        endif;


        $errors = $response->getErrorCodes() ?: array();
        $this->logger->notice( "Failed validation", [
            'remote_ip' => $remote_ip,
            'errors'    => join(",", $errors )
        ]);

        return false;
    }
}
