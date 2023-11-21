<?php

namespace Drupal\kamihaya_cms_spiral_api\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Psr\Log\LoggerInterface;

/**
 * The SPIRAL API client.
 */
class SpiralApi {

  use StringTranslationTrait;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The api url.
   *
   * @var string
   */
  protected $apiUrl;

  /**
   * The api key.
   *
   * @var string
   */
  protected $apiKey;
  /**
   * The application ID.
   *
   * @var string
   */
  protected $appId;

  /**
   * The api DB.
   *
   * @var string
   */
  protected $apiDb;

  /**
   * The site ID.
   *
   * @var string
   */
  protected $siteId;

    /**
   * The authentication ID.
   *
   * @var string
   */
  protected $authId;

  /**
   * The contructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   *  @param \Drupal\Core\Session\AccountInterface $current_user
   *   The user account.
   */
  public function __construct(
    LoggerInterface $logger,
    ConfigFactoryInterface $config_factory,
    Messenger $messenger,
    TranslationInterface $string_translation,
    AccountInterface $current_user) {
    // Setup the logger.
    $this->logger = $logger;
    // Setup the configuration factory.
    $this->config = $config_factory->get('spiral_api.settings');
    // Setup the messenger.
    $this->messenger = $messenger;
    // Setup the $this->t()
    $this->stringTranslation = $string_translation;
    $this->currentUser = $current_user;
    $this->apiUrl = $this->config->get('spiral_api_url');
    $this->apiKey = $this->config->get('spiral_api_key');
    $this->appId = $this->config->get('spiral_application_id');
    $this->apiDb = $this->config->get('spiral_db_id');
    $this->siteId = $this->config->get('spiral_site_id');
    $this->authId = $this->config->get('spiral_authentication_id');
  }

  /**
   * Create the Data.
   *
   * @param array $insert_data
   *   The array of insert data.
   */
  public function create($insert_data) {
    // HTTP header for API.
    $api_headers = [
      "Authorization： Bearer　{$this->apiKey}",
      "Content-Type: application/json; charset=UTF-8",
    ];

    // Set up the parameter.
    $parameters = [];
    // Token.
    $parameters["spiral_api_token"] = $this->config->get('spiral_api_token');
    // DB title.
    $parameters["db_title"] = $this->apiDb;
    // Epoc time.
    $parameters["passkey"] = time();

    $parameters["data"] = [];
    if (!empty($insert_data)) {
      foreach ($insert_data as $key => $value) {
        $parameters["data"][] = ["name" => $key, "value" => $value];
      }
    }

    // Signature.
    $key = $parameters["spiral_api_token"] . "&" . $parameters["passkey"];
    $parameters["signature"] = hash_hmac('sha1', $key, $this->config->get('spiral_api_secret'), FALSE);

    // Create the JSON data.
    $json = json_encode($parameters);

    // Send with curl.
    $url = "{$this->apiUrl}apps/{$this->appId}/db/{$this->apiDb}/records";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, TRUE);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $api_headers);
    curl_exec($curl);

    // Error handling.
    if (curl_errno($curl)) {
      return ["code" => curl_errno($curl), "message" => "curl error"];
    }

    $response = curl_multi_getcontent($curl);
    curl_close($curl);
    return json_decode($response, TRUE);
  }

  /**
   * Update with ID.
   *
   * @param int $edit_id
   *   The ID for updating.
   * @param array $update_data
   *   The array for updating data.
   */
  public function updateById($edit_id, array $update_data) {
    // HTTP header for API.
    $api_headers = [
      "Authorization： Bearer　{$this->apiKey}",
      "Content-Type: application/json; charset=UTF-8",
    ];

    // Set up the parameter.
    $parameters = [];
    // Token.
    $parameters["spiral_api_token"] = $this->config->get('spiral_api_token');
    // DB title.
    $parameters["db_title"] = $this->apiDb;
    // Epoc time.
    $parameters["passkey"] = time();

    $parameters["id"] = $edit_id;

    $parameters["data"] = [];
    if (!empty($update_data)) {
      foreach ($update_data as $key => $value) {
        $parameters["data"][] = ["name" => $key, "value" => $value];
      }
    }

    // Signature.
    $key = $parameters["spiral_api_token"] . "&" . $parameters["passkey"];
    $parameters["signature"] = hash_hmac('sha1', $key, $this->config->get('spiral_api_secret'), FALSE);

    // Create the JSON data.
    $json = json_encode($parameters);

    // Send with curl.
    $url = "{$this->apiUrl}apps/{$this->appId}/db/{$this->apiDb}/records/";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, TRUE);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $api_headers);
    curl_exec($curl);

    // Error handling.
    if (curl_errno($curl)) {
      return ["code" => curl_errno($curl), "message" => "curl error"];
    }

    $response = curl_multi_getcontent($curl);
    curl_close($curl);
    return json_decode($response, TRUE);
  }

  /**
   * Update all.
   *
   * @param array $fields
   *   The array of fields.
   * @param array $values
   *   Thea array of values.
   * @param array $operator
   *   The array of operators.
   * @param array $update_data
   *   The array of updating data.
   */
  public function updateAll(
    array $fields = [],
    array $values = [],
    array $operator = [],
    array $update_data = []) {
    if (count($fields) != count($values) && count($fields) != count($operator)) {
      return ["code" => "000001", "message" => "function error no data"];
    }
    // HTTP header for API.
    $api_headers = [
      "Authorization： Bearer　{$this->apiKey}",
      "Content-Type: application/json",
    ];

    // Set up the parameter.
    $parameters = [];
    // Token.
    $parameters["spiral_api_token"] = $this->config->get('spiral_api_token');
    // DB title.
    $parameters["db_title"] = $this->apiDb;
    // Epoc time.
    $parameters["passkey"] = time();

    $parameters["search_condition"] = [];
    if (!empty($fields)) {
      $i = 0;
      foreach ($fields as $value) {
        $parameters["search_condition"][$i]["name"] = $value;
        $i++;
      }
      $i = 0;
      foreach ($values as $value) {
        $parameters["search_condition"][$i]["value"] = $value;
        $i++;
      }
      $i = 0;
      foreach ($operator as $value) {
        $parameters["search_condition"][$i]["operator"] = $value;
        $i++;
      }
    }

    $parameters["data"] = [];
    if (!empty($update_data)) {
      foreach ($update_data as $key => $value) {
        $parameters["data"][] = ["name" => $key, "value" => $value];
      }
    }

    // Signature.
    $key = $parameters["spiral_api_token"] . "&" . $parameters["passkey"];
    $parameters["signature"] = hash_hmac('sha1', $key, $this->config->get('spiral_api_secret'), FALSE);

    // Create the JSON data.
    $json = json_encode($parameters);

    // Send with curl.
    $url = "{$this->apiUrl}apps/{$this->appId}/db/{$this->apiDb}/records";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, TRUE);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $api_headers);
    curl_exec($curl);

    // Error handling.
    if (curl_errno($curl)) {
      return ["code" => curl_errno($curl), "message" => "curl error"];
    }

    $response = curl_multi_getcontent($curl);
    curl_close($curl);
    return json_decode($response, TRUE);
  }

  /**
   * Get one data.
   *
   * @param array $select_columns
   *   The array of select columns.
   * @param array $fields
   *   The array of fields.
   * @param array $values
   *   Thea array of values.
   * @param array $operator
   *   The array of operators.
   */
  public function readSearchOne(
    array $select_columns = [],
    array $fields = [],
    array $values = [],
    array $operator = []) {
    if (count($select_columns) == 0 && count($fields) != count($values) && count($fields) != count($operator)) {
      return ["code" => "000001", "message" => "function error no data"];
    }

    // HTTP header for API.
    $api_headers = [
      "Authorization:Bearer {$this->apiKey}",
      "Content-Type:application/json",
    ];

    // Set up the parameter.
    $parameters = [];

    $where = [];
    if (!empty($fields)
      && !empty($values)
      && !empty($operator)
      && count($fields) === count($values)
      && count($fields) === count($operator)) {
      foreach ($fields as $idx => $value) {
        $quote = is_string($values[$idx]) ? "'" : "";
        $where[] = "@$value{$operator[$idx]}{$quote}{$values[$idx]}{$quote}";
      }
    }
    $parameters[] = 'where=' . implode(' AND ', $where);

    // Send with curl.
    $url = "{$this->apiUrl}apps/{$this->appId}/dbs/{$this->apiDb}/records?";
    $url .= implode('&', $parameters);
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $api_headers);
    $response = curl_exec($curl);

    $error = curl_errno($curl);
    curl_close($curl);
    // Error handling.
    if ($error) {
      return ["code" => curl_errno($curl), "message" => "curl error"];
    }

    $data = json_decode($response, TRUE);

    // No result.
    if ($data["totalCount"] == 0) {
      return NULL;
    }
    $data = $data["items"][0];
    $return_data = [];
    foreach ($select_columns as $key => $value) {
      $return_data[$value] = $data[$key];
    }

    return $return_data;
  }

  /**
   * Get all data.
   *
   * @param array $select_columns
   *   The array of select columns.
   * @param array $fields
   *   The array of fields.
   * @param array $values
   *   Thea array of values.
   * @param array $operator
   *   The array of operators.
   */
  public function readSearchAll(
    array $select_columns = [],
    array $fields = [],
    array $values = [],
    array $operator = []) {

    if (count($select_columns) == 0 && count($fields) != count($values) && count($fields) != count($operator)) {
      return ["code" => "000001", "message" => "function error no data"];
    }

    // HTTP header for API.
    $api_headers = [
      "Authorization： Bearer　{$this->apiKey}",
      "Content-Type: application/json",
    ];

    // Set up the parameter.
    $parameters = [];
    // Token.
    $parameters["spiral_api_token"] = $this->config->get('spiral_api_token');
    // DB title.
    $parameters["db_title"] = $this->apiDb;
    // Epoc time.
    $parameters["passkey"] = time();

    // Display column name.
    $parameters["select_columns"] = $select_columns;

    $parameters["search_condition"] = [];
    if (!empty($fields)) {
      $i = 0;
      foreach ($fields as $value) {
        $parameters["search_condition"][$i]["name"] = $value;
        $i++;
      }
      $i = 0;
      foreach ($values as $value) {
        $parameters["search_condition"][$i]["value"] = $value;
        $i++;
      }
      $i = 0;
      foreach ($operator as $value) {
        $parameters["search_condition"][$i]["operator"] = $value;
        $i++;
      }
    }

    // Signature.
    $key = $parameters["spiral_api_token"] . "&" . $parameters["passkey"];
    $parameters["signature"] = hash_hmac('sha1', $key, $this->config->get('spiral_api_secret'), FALSE);

    // Create the JSON data.
    $json = json_encode($parameters);

    // Send with curl.
    $url = "{$this->apiUrl}apps/{$this->appId}/db/{$this->apiDb}/records";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, TRUE);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $api_headers);
    curl_exec($curl);

    // Error handling.
    if (curl_errno($curl)) {
      return ["code" => curl_errno($curl), "message" => "curl error"];
    }

    $response = curl_multi_getcontent($curl);
    curl_close($curl);
    $data = json_decode($response, TRUE);

    // Spiral error.
    if ($data["code"] != 0) {
      return $data;
    }

    // No data.
    if ($data["count"] == 0) {
      return NULL;
    }
    $data = $data["data"];
    $return_data = [];
    foreach ($data as $key2 => $value2) {
      $data_tmp = [];
      foreach ($select_columns as $key => $value) {
        $data_tmp[$value] = $value2[$key];
      }
      $return_data[] = $data_tmp;
    }

    return $return_data;
  }

  /**
   * Delete data with ID.
   *
   * @param int $id
   *   The ID to delete.
   */
  public function deleteById($id) {

    // HTTP header for API.
    $api_headers = [
      "Authorization： Bearer　{$this->apiKey}",
      "Content-Type: application/json",
    ];

    // Set up the parameter.
    $parameters = [];
    // Token.
    $parameters["spiral_api_token"] = $this->config->get('spiral_api_token');
    // DB title.
    $parameters["db_title"] = $this->apiDb;
    // Epoc time.
    $parameters["passkey"] = time();

    $parameters["id"] = $id;

    // Signature.
    $key = $parameters["spiral_api_token"] . "&" . $parameters["passkey"];
    $parameters["signature"] = hash_hmac('sha1', $key, $this->config->get('spiral_api_secret'), FALSE);

    // Create the JSON data.
    $json = json_encode($parameters);

    // Send with curl.
    $url = "{$this->apiUrl}apps/{$this->appId}/db/{$this->apiDb}/records";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, TRUE);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $api_headers);
    curl_exec($curl);

    // Error handling.
    if (curl_errno($curl)) {
      return ["code" => curl_errno($curl), "message" => "curl error"];
    }

    $response = curl_multi_getcontent($curl);
    curl_close($curl);
    return json_decode($response, TRUE);
  }

  /**
   * Delete all data.
   *
   * @param array $select_columns
   *   The array of select columns.
   * @param array $fields
   *   The array of fields.
   * @param array $values
   *   Thea array of values.
   * @param array $operator
   *   The array of operators.
   */
  public function deleteAll(
    array $select_columns = [],
    array $fields = [],
    array $values = [],
    array $operator = []) {
    if (count($select_columns) == 0 && count($fields) != count($values) && count($fields) != count($operator)) {
      return ["code" => "000001", "message" => "function error no data"];
    }

    // HTTP header for API.
    $api_headers = [
      "Authorization： Bearer　{$this->apiKey}",
      "Content-Type: application/json",
    ];

    // Set up the parameter.
    $parameters = [];
    // Token.
    $parameters["spiral_api_token"] = $this->config->get('spiral_api_token');
    // DB title.
    $parameters["db_title"] = $this->apiDb;
    // Epoc time.
    $parameters["passkey"] = time();

    $parameters["search_condition"] = [];
    if (!empty($fields)) {
      $i = 0;
      foreach ($fields as $value) {
        $parameters["search_condition"][$i]["name"] = $value;
        $i++;
      }
      $i = 0;
      foreach ($values as $value) {
        $parameters["search_condition"][$i]["value"] = $value;
        $i++;
      }
      $i = 0;
      foreach ($operator as $value) {
        $parameters["search_condition"][$i]["operator"] = $value;
        $i++;
      }
    }

    // Signature.
    $key = $parameters["spiral_api_token"] . "&" . $parameters["passkey"];
    $parameters["signature"] = hash_hmac('sha1', $key, $this->config->get('spiral_api_secret'), FALSE);

    // Create the JSON data.
    $json = json_encode($parameters);

    // Send with curl.
    $url = "{$this->apiUrl}apps/{$this->appId}/db/{$this->apiDb}/records";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, TRUE);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $api_headers);
    curl_exec($curl);

    // Error handling.
    if (curl_errno($curl)) {
      return ["code" => curl_errno($curl), "message" => "curl error"];
    }

    $response = curl_multi_getcontent($curl);
    curl_close($curl);
    return json_decode($response, TRUE);
  }

  /**
   * Send the thanks mail.
   *
   * @param int $thanks_mail_id
   *   The thanks mail ID.
   * @param int $user_id
   *   The user ID to send the thanks mail.
   */
  public function thanksMail($thanks_mail_id = 0, $user_id = 0) {
    if ($thanks_mail_id == 0 || $user_id == 0) {
      return FALSE;
    }

    // HTTP header for API.
    $api_headers = [
      "Authorization： Bearer　{$this->apiKey}",
      "Content-Type: application/json",
    ];

    // Set up the parameter.
    $parameters = [];
    // Token.
    $parameters["spiral_api_token"] = $this->config->get('spiral_api_token');
    // Thanks mail rule ID.
    $parameters["rule_id"] = $thanks_mail_id;
    // Record ID.
    $parameters["id"] = $user_id;
    // Epoc time.
    $parameters["passkey"] = time();

    // Signature.
    $key = $parameters["spiral_api_token"] . "&" . $parameters["passkey"];
    $parameters["signature"] = hash_hmac('sha1', $key, $this->config->get('spiral_api_secret'), FALSE);

    // Create the JSON data.
    $json = json_encode($parameters);

    // Send with curl.
    $url = "{$this->apiUrl}apps/{$this->appId}/db/{$this->apiDb}/records";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, TRUE);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $api_headers);
    curl_exec($curl);

    // Error handling.
    if (curl_errno($curl)) {
      return ["code" => curl_errno($curl), "message" => "curl error"];
    }

    $response = curl_multi_getcontent($curl);
    curl_close($curl);
    return json_decode($response, TRUE);

  }

  /**
   * Login.
   *
   * @param string $email
   *   The mail address.
   * @param array $password
   *   The password.
   */
  public function login($email, $password) {
    if (empty($email) || empty($password)) {
      return ["code" => "000001", "message" => "function error no data"];
    }

    // HTTP header for API.
    $api_headers = [
      "Authorization:Bearer {$this->apiKey}",
      "Content-Type:application/json",
    ];

    // Set up the parameter.
    $parameters = [];

    $parameters["id"] = $email;
    $parameters["password"] = $password;

    // Create the JSON data.
    $json = json_encode($parameters);

    // Send with curl.
    $url = "{$this->apiUrl}sites/{$this->siteId}/authentications/{$this->authId}/login";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $api_headers);
    curl_setopt($curl, CURLOPT_POST, TRUE);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
    $response = curl_exec($curl);

    $error = curl_errno($curl);
    curl_close($curl);
    // Error handling.
    if ($error) {
      return ["code" => curl_errno($curl), "message" => "curl error"];
    }

    $data = json_decode($response, TRUE);

    // No data.
    if (empty($data["recordId"])) {
      return NULL;
    }
    $record_id = $data["recordId"];

    return $this->readByRedordId($record_id);
  }

  /**
   * Get one data from record ID.
   *
   * @param string $recordId
   *   The record id.
   */
  public function readByRedordId($recordId) {
    if (empty($recordId)) {
      return ["code" => "000001", "message" => "function error no data"];
    }

    // HTTP header for API.
    $api_headers = [
      "Authorization:Bearer {$this->apiKey}",
      "Content-Type:application/json",
    ];

    // Send with curl.
    $url = "{$this->apiUrl}apps/{$this->appId}/dbs/{$this->apiDb}/records/{$recordId}";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $api_headers);
    $response = curl_exec($curl);

    $error = curl_errno($curl);
    curl_close($curl);
    // Error handling.
    if ($error) {
      return ["code" => curl_errno($curl), "message" => "curl error"];
    }

    $data = json_decode($response, TRUE);

    // No item.
    if (empty($data["item"])) {
      return NULL;
    }

    foreach($data['item'] as $key => $value) {
      if (strpos($key, '_') !== 0 && $key !== 'password') {
        continue;
      }
      unset($data['item'][$key]);
    }

    return $data["item"];
  }

  /**
   * Change the database.
   *
   * @param string $db
   *   The db name.
   */
  public function apiDbChange($db) {
    $this->apiDb = $db;
  }

  /**
   * Set the default database.
   */
  public function apiDbReset() {
    $this->apiDb = $this->config->get('spiral_db_id');
  }

}
