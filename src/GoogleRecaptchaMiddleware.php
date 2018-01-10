<?php
namespace Germania\GoogleRecaptcha;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;


class GoogleRecaptchaMiddleware implements LoggerAwareInterface
{
    use LoggerAwareTrait;


    /**
     * @var Callable
     */
    public $validator;

    /**
     * @var string
     */
    public $input_field;

    /**
     * @var string
     */
    public $client_ip;

    /**
     * @var string
     */
    public $request_attribute;

    /**
     * @var int
     */
    public $http_status_code;

    /**
     * @var null|bool
     */
    public $status;


    public function __construct( callable $validator, $client_ip, $input_field, $request_attribute, $http_status_code, LoggerInterface $logger = null )
    {
        $this->validator   = $validator;
        $this->client_ip   = $client_ip;
        $this->input_field = $input_field;
        $this->request_attribute = $request_attribute;
        $this->http_status_code  = $http_status_code;

        $this->setLogger($logger ?: new NullLogger);
    }


    public function __invoke(Request $request, Response $response, $next)
    {
        $post_data = $request->getParsedBody() ?: array();

        $recaptcha_validator = $this->validator;

        if (empty($post_data[ $this->input_field ])
        or !$recaptcha_validator( $post_data[ $this->input_field ], $this->client_ip)):
            $this->status = false;
            $response = $response->withStatus( $this->http_status_code );
        else:
            $this->status = true;
        endif;

        // Store result in Request
        $request = $request->withAttribute( $this->request_attribute, [
            'failed'  => !$this->status,
            'success' => $this->status,
            'status'  => $this->status
        ]);

        // Call $next middleware
        return $next($request, $response);

    }
}
