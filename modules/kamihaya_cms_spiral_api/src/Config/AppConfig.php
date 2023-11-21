<?php

namespace Drupal\kamihaya_cms_spiral_api\Config;

class AppConfig {

  public static function config() {
    $app_config = [];
    $app_config["site_variable"]["spiral_fields"] = [
      "id",
      "sso_uid",
      "name_sei",
      "name_mei",
      "email",
      "password",
      "status",
      "del_flg",
    ];

    return $app_config;
  }
}
