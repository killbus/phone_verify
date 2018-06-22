<?php

namespace Drupal\phone_verify\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingForm.
 */
class SettingForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'phone_verify.setting',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'phone_verify_setting_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('phone_verify.setting');
    $form['sms_template'] = [
      '#type' => 'textarea',
      '#title' => $this->t('SMS Template'),
      '#default_value' => $config->get('sms_template'),
    ];
    $form['sms_remote_template'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SMS Remote template'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('sms_remote_template'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('phone_verify.setting')
      ->set('sms_template', $form_state->getValue('sms_template'))
      ->set('sms_remote_template', $form_state->getValue('sms_remote_template'))
      ->save();
  }

}
