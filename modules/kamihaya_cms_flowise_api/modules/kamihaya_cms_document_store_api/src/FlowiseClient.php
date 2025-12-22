<?php

declare(strict_types=1);

namespace Drupal\kamihaya_cms_document_store_api;

use Drupal\kamihaya_cms_flowise_api\FlowiseClientBase;

/**
 * Flowise API Client for Document Store.
 */
final class FlowiseClient extends FlowiseClientBase {

  /**
   * Get the document store by ID.
   *
   * @param string $document_store_id
   *   The document store ID.
   *
   * @return array
   *   The document store data.
   */
  public function getDocumentStore(string $document_store_id): array {
    return $this->request('GET', "/document-store/store/{$document_store_id}");
  }

  /**
   * Get the list of document stores.
   *
   * @return array
   *   The list of document stores.
   */
  public function getDocumentStoreList(): array {
    return $this->request('GET', "/document-store/store");
  }

  /**
   * Upsert the document store.
   *
   * @param string $document_store_id
   *   The document store ID.
   * @param array $document_store_detail
   *   The document store details.
   *
   * @return array
   *   The response from the API.
   */
  public function upsertDocumenStore(string $document_store_id, array $document_store_detail = []):  array {
    if (empty($document_store_detail)) {
      $document_store_detail = $this->getDocumentStore($document_store_id);
      if (empty($document_store_detail)) {
        $this->logger->error('Document store with ID @id not found.', ['@id' => $document_store_id]);
        return [];
      }
    }
    $items = $this->createItems($document_store_detail);
    if (empty($items)) {
      $this->logger->error('Failed to create items for document store upsert.');
      return [];
    }
    return $this->request(
      'POST',
      "/document-store/upsert/{$document_store_id}",
      [
        'json' => $items,
        'timeout' => 60,
      ]
    );
  }

  /**
   * Re-process andd upsert the document store.
   *
   * @param string $document_store_id
   *   The document store ID.
   * @param array $document_store_detail
   *   The document store details.
   *
   * @return array
   *   The response from the API.
   */
  public function refreshDocumenStore(string $document_store_id, array $document_store_detail = []): array {
    if (empty($document_store_detail)) {
      $document_store_detail = $this->getDocumentStore($document_store_id);
      if (empty($document_store_detail)) {
        $this->logger->error('Document store with ID @id not found.', ['@id' => $document_store_id]);
        return [];
      }
    }
    $items = $this->createItems($document_store_detail);
    if (empty($items)) {
      $this->logger->error('Failed to create items for document store upsert.');
      return [];
    }
    return $this->request(
      'POST',
      "/document-store/refresh/{$document_store_id}",
      [
        'json' => $items,
        'timeout' => 60,
      ]
    );
  }

  /**
   * Create items for the document store.
   *
   * @param array $document_store_detail
   *   The document store details.
   *
   * @return array
   *   The formatted items for the request.
   */
  protected function createItems(array $document_store_detail): array {
    $loader = !empty($document_store_detail['loaders']) ? $document_store_detail['loaders'][0] : [];
    if (empty($loader)) {
      return [];
    }
    return [
      "docId" => $loader['id'] ?? '',
      "metadata" => $loader['metadata']['loaderConfig']['metadata'] ?? [],
      "replaceExisting" => TRUE,
      "createNewDocStore" => FALSE,
    ];
  }

}
