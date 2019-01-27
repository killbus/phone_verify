# phone_verify

Provides phone number verify functionality.

Features:
- RestResource plugin `phone_verify_sms_code_verify`。This provides an api to send a sms verify code to a phone number.
- Setting form to define the sms message template of the verify code message.
- Service `phone_verify.sms_code_verifier`, to verify the code by developer.

This module is in use by the [user_phone](https://www.drupal.org/project/user_phone) module, 
which provides user-phone binding, and phone-sms-login functionality.
