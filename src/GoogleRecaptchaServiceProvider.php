<?php
namespace Germania\GoogleRecaptcha;

use Pimple\Container as PimpleContainer;
use Pimple\ServiceProviderInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

use ReCaptcha\ReCaptcha;

class GoogleRecaptchaServiceProvider implements ServiceProviderInterface, LoggerAwareInterface
{

    use LoggerAwareTrait;

    /**
     * @var string
     */
    public $public_key;

    /**
     * @var string
     */
    public $secret_key;



    /**
     * @param string               $public_key
     * @param string               $secret_key
     * @param LoggerInterface|null $logger
     */
    public function __construct( $public_key, $secret_key, LoggerInterface $logger = null)
    {
        $this->secret_key = $secret_key;
        $this->public_key = $public_key;
        $this->setLogger( $logger ?: new NullLogger );
    }



    /**
     * @param PimpleContainer $dic Pimple Instance
     * @implements ServiceProviderInterface
     */
    public function register(PimpleContainer $dic)
    {

        /**
         * @return array
         */
        $dic['Google.Recaptcha.Config'] = function($dic) {
            return [
                'input_field'       => 'g-recaptcha-response',
                'status_code'       => 400,
                'request_attribute' => 'GoogleRecaptcha'
            ];
        };


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
         * @return string
         */
        $dic['Google.Recaptcha.ClientIP'] = function($dic) {
            return $_SERVER['REMOTE_ADDR'];
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


        /**
         * @return callable GoogleRecaptchaMiddleware
         */
        $dic['Google.Recaptcha.Middleware'] = function($dic) {

            $recaptcha_validator = $dic['Google.Recaptcha.Validator.Callable'];
            $client_ip           = $dic['Google.Recaptcha.ClientIP'];
            $logger              = $dic['Google.Recaptcha.Logger'];

            $config = $dic['Google.Recaptcha.Config'];
            $input_field         = $config['input_field'];
            $http_status_code    = $config['status_code'];
            $request_attribute   = $config['request_attribute'];

            return new GoogleRecaptchaMiddleware($recaptcha_validator, $client_ip, $input_field, $request_attribute, $http_status_code, $logger);

        };

    }
}
