<?php

namespace MakinaCorpus\Ucms\Search\Attachment;


/**
 * For performance reason, indexing attachments must be done into an another
 * separate process. Indeed, bulk update operations with files about ~5mo could
 * consume a lot of memory (100 x 5mo = 500mo of base64 file content encoded
 * in memory)!
 */
interface NodeAttachmentIndexerInterface
{

    /**
     * Process attachments indexations for some queued items.
     */
    public function bulkAttachmentDequeue();

    /**
     * Marks every nodes (all or from a given list of ID's) which needs to be
     * reindexed for attachments.
     *
     * @param array $nids
     */
    public function bulkMarkAttachmentForReindex(array $nids = null);
}
