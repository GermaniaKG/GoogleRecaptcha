<?php
namespace Germania\GoogleRecaptcha;

use Pimple\Container as PimpleContainer;
use Pimple\ServiceProviderInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use ReCaptcha\ReCaptcha;

class GoogleRecaptchaServices implements ServiceProviderInterface
{

    /**
     * @var string
     */
    public $public_key;

    /**
     * @var string
     */
    public $secret_key;

    /**
     * @var LoggerInterface
     */
    public $logger;



    /**
     * @param string               $public_key
     * @param string               $secret_key
     * @param LoggerInterface|null $logger
     */
    public function __construct( $public_key, $secret_key, LoggerInterface $logger = null)
    {
        $this->secret_key = $secret_key;
        $this->public_key = $public_key;
        $this->logger = $logger ?: new NullLogger;
    }



    /**
     * @param PimpleContainer $dic Pimple Instance
     * @implements ServiceProviderInterface
     */
    public function register(PimpleContainer $dic)
    {

        /**
         * @return string
         */
        $dic['Google.Recaptcha.PublicKey'] = function($dic) {
            return $this->public_key;
        };


        /**
         * @return string
         */
        $dic['Google.Recaptcha.SecretKey'] = function($dic) {
            return $this->secret_key;
        };


        /**
         * @return LoggerInterface
         */
        $dic['Google.Recaptcha.Logger'] = function($dic) {
            return $this->logger;
        };


        /**
         * @return ReCaptcha
         */
        $dic['Google.Recaptcha.Validator'] = function($dic) {
            $secret = $dic['Google.Recaptcha.SecretKey'];
            return new ReCaptcha($secret);
        };


        /**
         * @return callable GoogleRecaptchaCallable
         */
        $dic['Google.Recaptcha.Validator.Callable'] = function($dic) {
            $php_client = $dic['Google.Recaptcha.Validator'];
            $logger     = $dic['Google.Recaptcha.Logger'];

            return new GoogleRecaptchaCallable( $php_client, $logger );
        };

    }
}
