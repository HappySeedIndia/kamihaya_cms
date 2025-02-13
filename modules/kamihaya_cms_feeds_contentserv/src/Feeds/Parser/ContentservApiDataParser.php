<?php

namespace Drupal\kamihaya_cms_feeds_contentserv\Feeds\Parser;

use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Exception\FetchException;
use Drupal\feeds\Exception\SkipItemException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Feeds\Item\DynamicItem;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\Result\ParserResult;
use Drupal\feeds\StateInterface;
use Drupal\feeds\StateType;
use GuzzleHttp\Exception\GuzzleException;
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
    // Check the mapping sources.
    foreach ($feed->getType()->getMappingSources() as $key => $info) {
      if (isset($info['type']) && $info['type'] !== 'kamihaya_json') {
        $skip_sources[$key] = $key;
        continue;
      }
      if (!empty($info['value']) && trim(strval($info['value'])) !== '') {
        $sources[$key] = $info['value'];
      }
    }

    // Get the fetcher configuration.
    $fetcher_config = $feed->getType()->getFetcher()->getConfiguration();
    // Get the processor configuration.
    $processor_config = $feed->getType()->getProcessor()->getConfiguration();
    $data_type = $fetcher_config['data_type'];
    // Get the additional language code.
    $langcode = !empty($processor_config['addtional_langcode']) ? $processor_config['addtional_langcode'] : '';

    /** @var \Drupal\kamihaya_cms_feeds_contentserv\Result\ContentservApiFetcherResultInterface $fetcher_result */
    if (empty($fetcher_result->getResults())) {
      throw new EmptyFeedException(strtr('@name: There is no fetched data.', ['@name' => $feed->label()]));
    }
    $result = new ParserResult();
    $mappings = $feed->getType()->getMappings();
    try {
      $results = $fetcher_result->getResults();
      if (empty($results[0])) {
        throw new FetchException($this->t('@name: No detailed results found.', ['@name' => $feed->label()]));
      }
      $result_data = $results[0];
      $data_id = $result_data[$data_type]['ID'];
      $item = new DynamicItem();
      // Set the sources to the item.
      foreach ($sources as $key => $json_key) {
        if (isset($skip_sources[$key])) {
          continue;
        }
        // Get the value from the result data.
        $value = $this->getAttributeValue($result_data[$data_type], $json_key);
        // Check if the target is media.
        if (!empty($value) && $target = $this->getMediaTarget($feed, $key)) {
          // Get the label of the file.
          $label = $this->getAttributeValue($result_data[$data_type], 'Label');
          // Check the file extension.
          if (!$this->checkFileExtention($target, $label)) {
            // Skip the file if the file extension is not correct.
            $state->report(StateType::SKIP, strtr('Skipped because the file extentioon is not correct. [@type ID: @id, File label: @label, File ID: @value]', [
              '@type' => $data_type,
              '@id' => $data_id,
              '@label' => $label,
              '@value' => $value,
            ]), [
              'feed' => $feed,
              'item' => $item,
            ]);
            $state->setMessage(strtr('@name - Skipped because the file extentioon is not correct. [@type ID: @id, File label: @label, File ID: @value]', [
              '@name' => $feed->label(),
              '@type' => $data_type,
              '@id' => $data_id,
              '@label' => $label,
              '@value' => $value,
            ]), 'warning');
            if ($data_type == 'File') {
              return $result;
            }
            continue;
          }
          try {
            // Create the media file.
            $value = $this->createMediaFile($feed, $fetcher_result, $target, $value, $label);
          } catch (GuzzleException $e) {
            $state->report(StateType::FAIL, strtr('Skipped file because the file extentioon is not correct. [@type ID: @id, File label: @label, File ID: @value]', [
              '@type' => $data_type,
              '@id' => $data_id,
              '@label' => $label,
              '@value' => $value,
            ]), [
              'feed' => $feed,
              'item' => $item,
            ]);
            throw new SkipItemException(strtr('@name - Failed to create file. [@type ID: @id, File label: @label, File ID: @value, error: %error]', [
              '@name' => $feed->label(),
              '@type' => $data_type,
              '@id' => $data_id,
              '@label' => $label,
              '@value' => $value,
              '%error' => $e->getMessage(),
            ]));
          }
        }
        $item->set($key, $value);
      }

      if (!empty($langcode)) {
        // Get the additional language data.
        $url = $fetcher_config['json_api_url'];
        $data_url = "/core/v1/" . strtolower($data_type) . '/';
        $add_options = [];
        // Set the language code to the query.
        $add_options[RequestOptions::QUERY] = ['lang' => $langcode];
        if ($this->hasTagsInSource($feed)) {
          // Set the expand query if the feed has 'Tags' in source.
          $add_options[RequestOptions::QUERY]['expand'] = 'Tags';
        }
        $response = $this->getData($feed, $url, "$data_url{$data_id}", $fetcher_result->getAccessToken(), $add_options);
        $data = json_decode($response, TRUE);
        if (!empty($data[$data_type])) {
          $add_item = new DynamicItem();
          // Set the sources to the item.
          foreach ($sources as $key => $json_key) {
            if (isset($skip_sources[$key])) {
              continue;
            }

            // Get the value from the result data.
            $value = $this->getAttributeValue($data[$data_type], $json_key);
            $label = FALSE;
            // Check if the value is alt or description.
            foreach ($mappings as $mapping) {
              if ((empty($mapping['map']['alt']) || $mapping['map']['alt'] !== $key) && (empty($mapping['map']['description']) || $mapping['map']['description'] !== $key)) {
                continue;
              }
              $label = TRUE;
            }
            // Skip the value is not set or the value is same as the original language value and not alt or description.
            if (strlen($value) === 0 || ($value === $item->get($key) && !$label)) {
              continue;
            }
            // Check if the target is media.
            if (!empty($value) && $target = $this->getMediaTarget($feed, $key)) {
              // Get the label of the file.
              $label = $this->getAttributeValue($data[$data_type], 'Label');
              // Check the file extension.
              if (!$this->checkFileExtention($target, $label)) {
                continue;
              }
              try {
                // Create the media file.
                $value = $this->createMediaFile($feed, $fetcher_result, $target, $value, $label);
              } catch (GuzzleException $e) {
                throw new SkipItemException(strtr('@name - Failed to create file. [@type ID: @id, File label: @label, File ID: @value, error: %error]', [
                  '@name' => $feed->label(),
                  '@type' => $data_type,
                  '@id' => $data_id,
                  '@label' => $label,
                  '@value' => $value,
                  '%error' => $e->getMessage(),
                ]));
              }
            }
            $add_item->set($key, $value);
          }
          // Add the additional language data to the item.
          if (count($add_item->toArray())) {
            $add_item->set('translation', TRUE);
            $item->set($langcode, $add_item);
          }
        }
      }
      // Set the access token to the item.
      $item->set('access_token', $fetcher_result->getAccessToken());
      $result->addItem($item);
      $state->pointer = 1;

      // Report progress.
      $state->total = 1;
      $state->progress($state->total, $state->pointer);

    }
    catch (GuzzleException $e) {
      $state->setCompleted();
      $args = [
        '@name' => $feed->label(),
        '%error' => $e->getMessage()];
      throw new FetchException(strtr('@name - The error occurs while getting detailed data because of error "%error".', $args));
    }

    // Set progress to complete if no more results are parsed.
    if (!$result->count()) {
      $state->setCompleted();
    }

    return $result;
  }

}
