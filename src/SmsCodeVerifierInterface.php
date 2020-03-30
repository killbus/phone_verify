<?php

namespace Drupal\phone_verify;

/**
 * Interface SmsCodeVerifierInterface.
 */
interface SmsCodeVerifierInterface
{
  /**
   * 设置手机号最后发送的验证码，通过 State API 保存，以便在SmsCodeVerifierInterface::verify() 中验证
   *
   * @param $phone
   * @param $code
   */
  public function setCode($phone, $code);

  /**
   * 检查验证码是否有效
   *
   * @param $phone
   * @param $code
   * @return bool
   */
  public function verify($phone, $code);
}
