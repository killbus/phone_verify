<?php

namespace Drupal\phone_verify\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\phone_verify\SmsCodeVerifierInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use League\OAuth2\Server\Exception\OAuthServerException;
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
   * @var SmsCodeVerifierInterface
   */
  protected $smsCodeVerifier;

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
    AccountProxyInterface $current_user,
    SmsCodeVerifierInterface $sms_code_verifier)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
    $this->smsCodeVerifier = $sms_code_verifier;
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
      $container->get('current_user'),
      $container->get('phone_verify.sms_code_verifier')
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
    if (is_null($data['country']) || is_null($data['number'])) {
      throw new BadRequestHttpException('Phone number info is not complete.');
    }

    /** @var \Drupal\mobile_number\MobileNumberUtilInterface $util */
    $util = \Drupal::service('mobile_number.util');
    $mobileNumber = $util->getMobileNumber($data['number'], $data['country']);
    if ($mobileNumber) {

      $sms_code = Rand::getString(6, '0123456789');
      $salt = Rand::getString(15, 'abcdefghijklmnopqrstuvwxyz0123456789');

      // 发送短信
      /** @var \Drupal\sms\Provider\SmsProviderInterface $sms_service */
      $sms_service = \Drupal::service('sms.provider');

      $sms_config = \Drupal::config('phone_verify.setting');
      $sms = (new \Drupal\sms\Message\SmsMessage())
        ->setMessage(str_replace('{@code}', $sms_code, $sms_config->get('sms_template'))) // Set the message.
        ->addRecipient($util->getCallableNumber($mobileNumber)) // Set recipient phone number
        ->setDirection(\Drupal\sms\Direction::OUTGOING);

      $sms->setOption('remote_template', $sms_config->get('sms_remote_template'));
      $sms->setOption('remote_template_data', [
        'code' => $sms_code
      ]);

      try {
        $sms_service->send($sms);
        $this->smsCodeVerifier->setCode($util->getCallableNumber($mobileNumber), $sms_code);
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

  }

  public function permissions()
  {
    return [];
  }
}
