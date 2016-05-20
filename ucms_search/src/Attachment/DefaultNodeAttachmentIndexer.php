<?php

namespace MakinaCorpus\Ucms\Search\Attachment;

use Drupal\node\NodeInterface;

class DefaultNodeAttachmentIndexer extends AbstractNodeAttachmentIndexer
{
    protected function getSubselectQueryMarkReindex(array $nids = null)
    {
        // List of fields is empty. Maybe to disable the feature?
        $content_types = $this->getAttachmentContentTypes();
        if (!$content_types) {
            return;
        }

        // Returns any nodes which have a content type with a field in the
        // hardcoded list of fields self::fields
        $query = $this
            ->db
            ->select('node', 'n')
            ->fields('n', ['nid'])
            ->condition('n.type', $content_types, 'IN');

        // If not, mark all nodes of these types!
        if (null !== $nids) {
            $query->condition('n.nid', $nids, 'IN');
        }

        return $query;
    }

    private function getMediaIdListForNode(NodeInterface $node)
    {
        $exists = $this
            ->db
            ->select('node', 'n')
            ->fields('n', ['nid']) // Should be "1" but addExpression breaks chaining
            ->condition('n.status', 1)
            ->where('n.nid = r.target_id')
        ;

        return $this
            ->db
            ->select('ucms_node_reference', 'r')
            ->fields('r', ['target_id'])
            ->condition('r.type', 'media')
            ->condition('r.source_id', $node->id())
            ->exists($exists)
            ->execute()
            ->fetchCol()
        ;
    }

    protected function getEncodedAttachments(NodeInterface $node)
    {
        $attachments = [];

        $mediaIdList = $this->getMediaIdListForNode($node);

        if (!$mediaIdList) {
            return [];
        }

        $medias = $this->nodeStorage->loadMultiple($mediaIdList);

        foreach ($medias as $media) {
            if (!in_array($media->type, ['document'], true)) {
                continue;
            }

            $files = field_get_items('node', $media, 'file');
            foreach ($files as $file) {

                $this->totalSizeBytes += $file['filesize'];
                if ($this->totalSizeBytes >= $this->bulkMaxSizeBytes) {
                    throw new MaxSizeAttachmentExceedException();
                }

                $attachments[] = base64_encode(
                    file_get_contents($file['uri'])
                );
            }
        }

        return $attachments;
    }

    /**
     * Returns a list of content types which used one of the hardcoded fields in
     * self::fields.
     */
    private function getAttachmentContentTypes()
    {
        $content_types = [];

        foreach ($this->fields as $fieldname) {
            $info = field_info_field($fieldname);
            if (!empty($info['bundles']['node'])) {
                $content_types += $info['bundles']['node'];
            }
        }

        return array_unique($content_types);
    }
}
