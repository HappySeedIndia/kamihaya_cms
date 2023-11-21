<?php

namespace Drupal\kamihaya_cms_spiral_api\Controller;

/**
 * The class for SPIRAL login controller.
 */
class LoginController extends SpiralController {

  /**
   * Login validation.
   *
   * @param string $email
   *   The email.
   * @param string $password
   *   The password.
   */
  public function loginValidate($email, $password) {
    $user = $this->spiralApi->login($email, $password);

    if (!empty($user["code"])) {
      // Handling the SPIRAL error.
      $this->logger->error("error code[" . $user["code"] . "]message[" . $user["message"] . "]");
      $this->messenger->addError("Error occurs while loggin in. Please ask to the site administrator.");
      return FALSE;
    }

    if (empty($user) || empty($user["sso_uid"]) || !empty($user['del_flg'])) {
      return FALSE;
    }

    if ($user['status'] === self::USER_STATUS_DENY || $user['status'] === self::USER_STATUS_DEL) {
      return FALSE;
    }
    // 保存.
    $login_data = [];
    $login_data = $user;
    $this->sessionManager->regenerate();
    $this->session->set(self::SPIRAL_API_SESSION_LOGIN_DATA, $login_data);
    return $user;
  }

}
