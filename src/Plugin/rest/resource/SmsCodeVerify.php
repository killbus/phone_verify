<?php

namespace Drupal\phone_verify\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Zend\Math\Rand;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "phone_verify_sms_code_verify",
 *   label = @Translation("Sms code verify"),
 *   uri_paths = {
 *     "create" = "/api/rest/phone-verify/sms-code-verify"
 *   }
 * )
 */
class SmsCodeVerify extends ResourceBase
{

    /**
     * A current user instance.
     *
     * @var \Drupal\Core\Session\AccountProxyInterface
     */
    protected $currentUser;

    /**
     * Constructs a new SmsCodeVerify object.
     *
     * @param array $configuration
     *   A configuration array containing information about the plugin instance.
     * @param string $plugin_id
     *   The plugin_id for the plugin instance.
     * @param mixed $plugin_definition
     *   The plugin implementation definition.
     * @param array $serializer_formats
     *   The available serialization formats.
     * @param \Psr\Log\LoggerInterface $logger
     *   A logger instance.
     * @param \Drupal\Core\Session\AccountProxyInterface $current_user
     *   A current user instance.
     */
    public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        array $serializer_formats,
        LoggerInterface $logger,
        AccountProxyInterface $current_user)
    {
        parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

        $this->currentUser = $current_user;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->getParameter('serializer.formats'),
            $container->get('logger.factory')->get('phone_verify'),
            $container->get('current_user')
        );
    }

    /**
     * Responds to POST requests.
     *
     * @param $data
     * @return \Drupal\rest\ModifiedResourceResponse
     *   The HTTP response object.
     *
     */
    public function post($data)
    {
        // You must to implement the logic of your REST Resource here.
        // Use current user after pass authentication to validate access.
        if (!$this->currentUser->hasPermission('access content')) {
            throw new AccessDeniedHttpException();
        }

        $sms_code = Rand::getString(6, '0123456789');
        $salt = Rand::getString(15, 'abcdefghijklmnopqrstuvwxyz0123456789');

        // 发送短信
        /** @var \Drupal\sms\Provider\SmsProviderInterface $sms_service */
        $sms_service = \Drupal::service('sms.provider');
        $sms = (new \Drupal\sms\Message\SmsMessage())
            ->setMessage('您的验证码是【'.$sms_code.'】，打死都不告诉别人。') // Set the message.
            ->addRecipient($data['phone']) // Set recipient phone number
            ->setDirection(\Drupal\sms\Direction::OUTGOING);
        try {
            $sms_service->send($sms);
        }
        catch (\Drupal\sms\Exception\RecipientRouteException $e) {
            // Thrown if no gateway could be determined for the message.
            throw new BadRequestHttpException('没有配置短信网关');
        }
        catch (\Exception $e) {
            // Other exceptions can be thrown.
            throw new BadRequestHttpException('发送短信出错');
        }

        $result = md5($sms_code . $salt);
        return new ModifiedResourceResponse([
            'salt' => $salt,
            'result' => $result
        ], 200);
    }

    public function permissions()
    {
        return [];
    }
}
