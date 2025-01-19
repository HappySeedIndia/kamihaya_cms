<?php

namespace Drupal\kamihaya_cms_feeds_contentserv\Feeds\Parser;

use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Exception\FetchException;
use Drupal\feeds\Exception\ValidationException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\DynamicItem;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\Result\ParserResult;
use Drupal\feeds\StateInterface;
use Drupal\feeds\StateType;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;

/**
 * Defines a Contentserv API feed parser.
 *
 * @FeedsParser(
 *   id = "kamihaya_contentserv_data",
 *   title = "Kamihaya Contentserv Data",
 *   description = @Translation("Parse Contentserv JSON gotten by Kamihaya Contentserv API for one data."),
 * )
 */
class ContentservApiDataParser extends ContentservApiParser {

  /**
   * {@inheritdoc}
   */
  public function parse(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state) {
    // Get sources.
    $sources = [];
    $skip_sources = [];
    foreach ($feed->getType()->getMappingSources() as $key => $info) {
      if (isset($info['type']) && $info['type'] !== 'kamihaya_json') {
        $skip_sources[$key] = $key;
        continue;
      }
      if (!empty($info['value']) && trim(strval($info['value'])) !== '') {
        $sources[$key] = $info['value'];
      }
    }

    $fetcher_config = $feed->getType()->getFetcher()->getConfiguration();
    $processor_config = $feed->getType()->getProcessor()->getConfiguration();
    $data_type = $fetcher_config['data_type'];
    $langcode = !empty($processor_config['addtional_langcode']) ? $processor_config['addtional_langcode'] : '';

    /** @var \Drupal\kamihaya_cms_feeds_contentserv\Result\ContentservApiFetcherResultInterface $fetcher_result */
    if (empty($fetcher_result->getResults())) {
      throw new EmptyFeedException();
    }
    $result = new ParserResult();
    $mappings = $feed->getType()->getMappings();
    try {
      $results = $fetcher_result->getResults();
      if (empty($results)) {
        throw new FetchException($this->t('No results found.'));
      }
      $result_data = $results[0];
      $item = new DynamicItem();
      foreach ($sources as $key => $json_key) {
        if (isset($skip_sources[$key])) {
          continue;
        }

        $value = $this->getAttributeValue($result_data[$data_type], $json_key);
        if (!empty($value) && $target = $this->getMediaTarget($feed, $key)) {
          $label = $this->getAttributeValue($result_data[$data_type], 'Label');
          if (!$this->checkFileExtention($target, $label)) {
            $state->report(StateType::SKIP, 'Skipped because the file extentioon is not correct.', [
              'feed' => $feed,
              'item' => $item,
            ]);
            return $result;
          }
          $value = $this->createMediaFile($feed, $fetcher_result, $target, $value, $label);
        }
        $item->set($key, $value);
      }

      if (!empty($langcode)) {
        // Get additional language data.
        $url = $fetcher_config['json_api_url'];
        $data_url = "/core/v1/" . strtolower($data_type) . '/';
        $add_options = [];
        $add_options[RequestOptions::QUERY] = ['lang' => $langcode];
        $response = $this->getData($feed, $url, "$data_url{$result_data[$data_type]['ID']}", $fetcher_result->getAccessToken(), $add_options);
        $data = json_decode($response, TRUE);
        if (!empty($data[$data_type])) {
          $add_item = new DynamicItem();
          foreach ($sources as $key => $json_key) {
            if (isset($skip_sources[$key])) {
              continue;
            }

            $value = $this->getAttributeValue($data[$data_type], $json_key);
            $label = FALSE;
            foreach ($mappings as $mapping) {
              if ((empty($mapping['map']['alt']) || $mapping['map']['alt'] !== $key) && (empty($mapping['map']['description']) || $mapping['map']['description'] !== $key)) {
                continue;
              }
              $label = TRUE;
            }
            if (strlen($value) === 0 || ($value === $item->get($key) && !$label)) {
              continue;
            }
            if (!empty($value) && $target = $this->getMediaTarget($feed, $key)) {
              $label = $this->getAttributeValue($data[$data_type], 'Label');
              if (!$this->checkFileExtention($target, $label)) {
                continue;
              }
              $value = $this->createMediaFile($feed, $fetcher_result, $target, $value, $label);
            }
            $add_item->set($key, $value);
          }
          if (count($add_item->toArray())) {
            $add_item->set('translation', TRUE);
            $item->set($langcode, $add_item);
          }
        }
      }
      $item->set('access_token', $fetcher_result->getAccessToken());
      $result->addItem($item);
      $state->pointer = 1;

      // Report progress.
      $state->total = 1;
      $state->progress($state->total, $state->pointer);

    }
    catch (RequestException $e) {
      $state->setCompleted();
      $args = ['%error' => $e->getMessage()];
      throw new FetchException(strtr('The error occurs while getting data because of error "%error".', $args));
    }

    // Set progress to complete if no more results are parsed.
    if (!$result->count()) {
      $state->setCompleted();
    }

    return $result;
  }

}
