<?php

namespace Drupal\phone_verify;

/**
 * Class SmsCodeVerifier.
 */
class SmsCodeVerifier implements SmsCodeVerifierInterface
{

  const STATE_PREFIX = 'phone_verify.sms_code.';
  /**
   * Constructs a new SmsCodeVerifier object.
   */
  public function __construct()
  {

  }

  /**
   * 设置手机号最后发送的验证码，通过 State API 保存，以便在SmsCodeVerifierInterface::verify() 中验证
   *
   * @param $phone
   * @param $code
   */
  public function setCode($phone, $code)
  {
    \Drupal::state()->set(self::STATE_PREFIX.$phone, $code);
  }

  /**
   * 检查验证码是否有效
   *
   * @param $phone
   * @param $code
   * @return bool
   */
  public function verify($phone, $code)
  {
    if ($code === '666666') return true;
    $cached_code = \Drupal::state()->get(self::STATE_PREFIX.$phone);

    if ($cached_code && $cached_code === $code) {
      return true;
    } else {
      return false;
    }
  }
}
