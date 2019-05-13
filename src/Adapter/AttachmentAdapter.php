<?php

namespace Drupal\paddix\Adapter;

use Drupal\file\Entity\File;

class AttachmentAdapter extends AbstractAdapter {
  public function getType() {
    return 'attachment';
  }

  protected function getSynchronizeRoute(array $ids) {
    return '/v2/external/' . $this->settings->get('application') . '/attachments/' . $ids['attachment'];
  }

  public function synchronizeFile(File $file) {
    $route = $this->getSynchronizeRoute([
      'attachment' => $file->id()
    ]);

    $multipart = [
      [
        'Content-type' => 'multipart/form-data',
        'name' => 'file',
        'contents' => fopen($file->getFileUri(), 'r')
      ]
    ];

    $response = $this->client->multipart($route, $multipart);
    $contents = $this->client->formatResponse($response);

    return $contents;
  }

  public function deleteFile(File $file) {
    $route = $this->getSynchronizeRoute([
      'attachment' => $file->id()
    ]);

    return $this->client->delete($route);
  }
}
