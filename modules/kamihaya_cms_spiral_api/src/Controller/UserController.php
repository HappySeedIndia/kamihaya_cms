<?php

namespace Drupal\kamihaya_cms_spiral_api\Controller;

use Drupal\spiral_api\Event\SpiralEvents;
use Drupal\spiral_api\Event\SpiralModifyEvent;

/**
 * The class for SPIRAL user controller.
 */
class UserController extends SpiralController {

  /**
   * Sent reminder mail.
   *
   * @param string @email
   *   The mail address.
   */
  public function sendReminderMail($email) {
    $spiral_data = $this->spiralApi->readSearchOne($this->siteConfig["spiral_fields"], [
      "email",
      "status",
      "status",
      "del_flg",
    ],
    [
      $email,
    // 非承認以外を抽出.
      self::USER_STATUS_DENY,
    // 退会以外を抽出.
      self::USER_STATUS_DEL,
      0,
    ],
    [
      "=",
      "!=",
      "!=",
      "=",
    ]);
    if (!empty($spiral_data["code"])) {
      // Handling the SPIRAL error..
      $this->logger->error("error code[" . $spiral_data["code"] . "]message[" . $spiral_data["message"] . "]");
      $this->messenger->addError("Error occurs in SPIRAL API. Please ask to the site administrator.");
      return FALSE;
    }
    if (empty($spiral_data["id"])) {
      // No data.
      $this->messenger->addError("ご登録のないメールアドレスです。");
      return FALSE;
    }

    // Create password hush and create random numer.
    $user_data = [];
    $user_data["reminder"] = $this->makeRandStr(4) . md5($email) . $this->makeRandStr(5);
    $user_data["update_at"] = date("Y-m-d H:i:s");

    $return_data = $this->spiralApi->updateById($spiral_data["id"], $user_data);
    if (!empty($return_data["code"])) {
      // Handling the SPIRAL error..
      $this->logger->error("error code[" . $return_data["code"] . "]message[" . $return_data["message"] . "]");
      $this->messenger->addError("Error occurs in SPIRAL API. Please ask to the site administrator.");
      return FALSE;
    }

    $return_data = $this->spiralApi->thanksMail($this->config->get('spiral_mail_reminder_id'), $spiral_data["id"]);
    if ($return_data["code"] != 0) {
      // Handling the SPIRAL error..
      $this->logger->error("error code[" . $return_data["code"] . "]message[" . $return_data["message"] . "]");
      $this->messenger->addError("Error occurs in SPIRAL API. Please ask to the site administrator.");
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Check the hush number.
   *
   * @param string $hash
   *   The hash value.
   */
  public function checkHash($hash) {
    // Confirm the data existance with code.
    $user_data = $this->spiralApi->readSearchOne($this->siteConfig["spiral_fields"], [
      "reminder",
      "update_at",
      "status",
      "status",
      "del_flg",
    ], [
      $hash,
      date("Y-m-d H:i:s", strtotime("-" . self::PASS_REMINDER_LIMIT_TIME . " day")),
      self::USER_STATUS_DENY,
      self::USER_STATUS_DEL,
      0,
    ], [
      "=",
      ">=",
      "!=",
      "!=",
      "=",
    ]);
    if (!empty($user_data["code"])) {
      // Handling the SPIRAL error..
      $this->logger->error("error code[" . $user_data["code"] . "]message[" . $user_data["message"] . "]");
      $this->messenger->addError("Error occurs in SPIRAL API. Please ask to the site administrator.");
      return FALSE;
    }

    if (empty($user_data)) {
      $this->messenger->addError("パスワードリマインダーの有効期限が切れているかデータが見当たりません。");
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Reset password.
   *
   * @param string $hash
   *   The hash value.
   * @param string $pass
   *   The password.
   */
  public function resetPassword($hash, $pass) {
    // Confirm the data existance with code.
    $user_data = $this->spiralApi->readSearchOne($this->siteConfig["spiral_fields"], [
      "reminder",
      "update_at",
      "status",
      "status",
      "del_flg",
    ],
    [
      $hash,
      date("Y-m-d H:i:s", strtotime("-" . self::PASS_REMINDER_LIMIT_TIME . " day")),
      self::USER_STATUS_DENY,
      self::USER_STATUS_DEL,
      0.,
    ],
    [
      "=",
      ">=",
      "!=",
      "!=",
      "=",
    ]);
    if (!empty($user_data["code"])) {
      // Handling the SPIRAL error..
      $this->logger->error("error code[" . $user_data["code"] . "]message[" . $user_data["message"] . "]");
      $this->messenger->addError("Error occurs in SPIRAL API. Please ask to the site administrator.");
      return FALSE;
    }

    if (empty($user_data)) {
      $this->messenger->addError("パスワードリマインダーの有効期限が切れているかデータが見当たりません。");
      return FALSE;
    }

    // Update password.
    $update_data = [];
    $update_data["password"] = $pass;
    $update_data["password1"] = $user_data["password"];
    $update_data["reminder"] = "";
    $update_data["update_at"] = date("Y-m-d H:i:s");
    $return_data = $this->spiralApi->updateById($user_data["id"], $update_data);
    if (!empty($return_data["code"])) {
      // Handling the SPIRAL error..
      $this->logger->error("error code[" . $return_data["code"] . "]message[" . $return_data["message"] . "]");
      $this->messenger->addError("Error occurs in SPIRAL API. Please ask to the site administrator.");
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Change password.
   *
   * @param string $password
   *   The password.
   */
  public function changePassword($password) {
    // Get the user data from session.
    $user_data = $this->session->get(self::SPIRAL_API_SESSION_LOGIN_DATA);

    // Update password.
    $update_data = [];
    $update_data["password"] = $password;
    $update_data["password1"] = $user_data["password"];
    $update_data["update_at"] = date("Y-m-d H:i:s");

    $return_data = $this->spiralApi->updateById($user_data["id"], $update_data);
    if (!empty($return_data["code"])) {
      // Handling the SPIRAL error..
      $this->logger->error("error code[" . $return_data["code"] . "]message[" . $return_data["message"] . "]");
      $this->messenger->addError("Error occurs in SPIRAL API. Please ask to the site administrator.");
      return FALSE;
    }
    $user_data['password'] = "md5:" . md5($password);
    $this->session->set(self::SPIRAL_API_SESSION_LOGIN_DATA, $user_data);
    return TRUE;
  }

  /**
   * Check the mail duplication.
   *
   * @param string $email
   *   The mail address.
   */
  public function checkMailDuplicate($email) {
    // Get the user data from session.
    $user_data = $this->session->get(self::SPIRAL_API_SESSION_LOGIN_DATA);

    $chk_data = $this->spiralApi->readSearchOne($this->siteConfig["spiral_fields"], [
      "id",
      "email",
      "del_flg",
    ], [
      $user_data["id"],
      $email,
      0,
    ], [
      "!=",
      "=",
      "=",
    ]);
    if (!empty($chk_data["code"])) {
      // Handling the SPIRAL error..
      $this->logger->error("error code[" . $chk_data["code"] . "]message[" . $chk_data["message"] . "]");
      $this->messenger->addError("Error occurs in SPIRAL API. Please ask to the site administrator.");
      return FALSE;
    }
    if (!empty($chk_data)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Update user data.
   *
   * @param array $data
   *   The array of the user information.
   */
  public function modifyUser($data) {
    // Get the user data from session.
    $user_data = $this->session->get(self::SPIRAL_API_SESSION_LOGIN_DATA);

    $update_data = [
      "user" => [],
      "admin" => [],
    ];
    $update_data["user"]["update_at"] = date("Y-m-d H:i:s");
    $update_data = $this->modFields($update_data, $user_data, $data, "name_sei");
    $update_data = $this->modFields($update_data, $user_data, $data, "name_mei");
    $update_data = $this->modFields($update_data, $user_data, $data, "sei_kana");
    $update_data = $this->modFields($update_data, $user_data, $data, "mei_kana");
    $update_data = $this->modFields($update_data, $user_data, $data, "email");
    $update_data = $this->modFields($update_data, $user_data, $data, "syokushu");
    $update_data = $this->modFields($update_data, $user_data, $data, "zip_code");
    $update_data = $this->modFields($update_data, $user_data, $data, "prefecture");
    $update_data = $this->modFields($update_data, $user_data, $data, "address");
    $update_data = $this->modFields($update_data, $user_data, $data, "facility");
    $update_data = $this->modFields($update_data, $user_data, $data, "department");
    $update_data = $this->modFields($update_data, $user_data, $data, "magazine");

    $return_data = $this->spiralApi->updateById($user_data["id"], $update_data["user"]);
    if (!empty($return_data["code"])) {
      // Handling the SPIRAL error..
      $this->logger->error("error code[" . $return_data["code"] . "]message[" . $return_data["message"] . "]");
      $this->messenger->addError("Error occurs in SPIRAL API. Please ask to the site administrator.");
      return FALSE;
    }

    // メール用のデータベースにデータを格納してメールを飛ばして速攻データを削除する.
    $this->spiralApi->apiDbChange(self::SPIRAL_API_DB_MOD);
    $update_data["admin"]["sso_uid"] = $user_data["sso_uid"];
    $admin_mail = $this->config->get('spiral_mail_admin_address');
    if (!empty($admin_mail) && $this->config->get('product_site')) {
      // ※本番は定数を使って管理者に送付する
      $update_data["admin"]["admin_mail"] = $admin_mail;
      $this->logger->debug(t("会員情報変更で管理者(@mail)に送信", ['@mail' => $admin_mail]));
    }
    else {
      // テスト用…テストなので登録ユーザーに報告メールが飛ぶようにしておく.
      $update_data["admin"]["admin_mail"] = $user_data["email"];
      $this->logger->debug(t("会員情報変更で登録アドレス(@mail)に送信", ['@mail' => $user_data["email"]]));
    }

    $spiral_data = $this->spiralApi->create($update_data["admin"]);
    if (!empty($return_data["code"])) {
      // Handling the SPIRAL error..
      $this->logger->error("error code[" . $spiral_data["code"] . "]message[" . $spiral_data["message"] . "]");
      $this->messenger->addError("Error occurs in SPIRAL API. Please ask to the site administrator.");
      return FALSE;
    }

    $this->logger->debug(t("会員変更実行スパイラル実行データ[@data]", ['@data' => '<pre><code>' . print_r($spiral_data, TRUE) . '</code></pre>']));
    // メールとバス.
    $spiral_status = $this->spiralApi->thanksMail($this->config->get('spiral_mail_user_mod_id'), $spiral_data["id"]);
    if ($spiral_status["code"] != 0) {
      // Handling the SPIRAL error..
      $this->logger->error("error code[" . $spiral_status["code"] . "]message[" . $spiral_status["message"] . "]");
      $this->messenger->addError("Error occurs in SPIRAL API. Please ask to the site administrator.");
      return FALSE;
    }

    $this->logger->debug(t("会員変更メール送信スパイラル実行データ[@data]", ['@data' => '<pre><code>' . print_r($spiral_data, TRUE) . '</code></pre>']));
    // 削除.
    $spiral_status = $this->spiralApi->deleteById($spiral_data["id"]);
    if ($spiral_status["code"] != 0) {
      // Handling the SPIRAL error..
      $this->logger->error("error code[" . $spiral_status["code"] . "]message[" . $spiral_status["message"] . "]");
      $this->messenger->addError("Error occurs in SPIRAL API. Please ask to the site administrator.");
      return FALSE;
    }
    $this->logger->debug(t("会員変更メール送信スパイラル実行データ[@data]", ['@data' => '<pre><code>' . print_r($spiral_data, TRUE) . '</code></pre>']));

    $this->spiralApi->apiDbReset();
    // セッション入替.
    $user_data = $this->loginUser(1);
    if ($user_data) {
      $this->session->set(self::SPIRAL_API_SESSION_LOGIN_DATA, $user_data);
    }
    $this->eventDispatcher->dispatch(SpiralEvents::MODIFY, new SpiralModifyEvent($user_data));
    return TRUE;
  }

  /**
   *
   */
  private function modFields($update_data, $user_data, $input_data, $field) {
    if ($user_data[$field] == $input_data[$field]) {
      $update_data["user"][$field . "1"] = "";
    }
    else {
      $update_data["user"][$field . "1"] = $user_data[$field];
      $update_data["admin"][$field] = $input_data[$field];
    }
    $update_data["user"][$field] = $input_data[$field];

    return $update_data;
  }

  /**
   * Check the password reminder mail..
   */
  public function checkMail($email) {
    // Check the duplication.
    $chk_data = $this->spiralApi->readSearchOne($this->siteConfig["spiral_fields"], [
      "email",
      "status",
      "status",
      "del_flg",
    ], [
      $email,
      self::USER_STATUS_DENY,
      self::USER_STATUS_DEL,
      0,
    ], [
      "=",
      "!=",
      "!=",
      "=",
    ]);
    if (!empty($chk_data["code"])) {
      // Handling the SPIRAL error..
      $this->logger->error("error code[" . $chk_data["code"] . "]message[" . $chk_data["message"] . "]");
      $this->messenger->addError("Error occurs in SPIRAL API. Please ask to the site administrator.");
      return FALSE;
    }
    $chk_data2 = TRUE;

    if (empty($chk_data["id"]) || $chk_data2 == FALSE) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

}
