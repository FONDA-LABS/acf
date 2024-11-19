<?php

namespace Corcel\Acf\Field;

use Corcel\Model\Post;
use Corcel\Model\Meta\PostMeta;
use Corcel\Acf\FieldInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class Image.
 *
 * @author Junior Grossi
 */
class Image extends BasicField implements FieldInterface
{
    /**
     * @var int
     */
    public $width;

    /**
     * @var int
     */
    public $height;

    /**
     * @var string
     */
    public $filename;

    /**
     * @var string
     */
    public $description;

    /**
     * @var string
     */
    public $url;

    /**
     * @var string
     */
    public $mime_type;

    /**
     * @var array
     */
    protected $sizes = [];

    /**
     * @var bool
     */
    protected $loadFromPost = false;

    /**
     * @param string $field
     */
    public function process($field)
    {
        $attachmentId = $this->fetchValue($field);

        $connection = $this->post->getConnectionName();

        if ($attachment = Post::on($connection)->find(intval($attachmentId))) {
            $this->fillFields($attachment);

            $imageData = $this->fetchMetadataValue($attachment);

            $this->fillMetadataFields($imageData);
        }
    }

    /**
     * @return Image
     */
    public function get()
    {
        return $this;
    }

    /**
     * @param Post $attachment
     */
    protected function fillFields(Post $attachment)
    {
        $this->attachment = $attachment;

        $this->mime_type = $attachment->post_mime_type;
        $this->url = $attachment->guid;
        $this->description = $attachment->post_excerpt;
    }

    /**
     * @param string $size
     * @param bool $useOriginalFallback
     *
     * @return Image
     */
    public function size($size, $useOriginalFallback = false)
    {
        if (isset($this->sizes[$size])) {
            return $this->fillThumbnailFields($this->sizes[$size]);
        }

        return $useOriginalFallback ? $this : $this->fillThumbnailFields($this->sizes['thumbnail']);
    }

    /**
     * @param array $data
     *
     * @return Image
     */
    protected function fillThumbnailFields(array $data)
    {
        $size = new static($this->post);
        $size->filename = $data['file'];
        $size->width = $data['width'];
        $size->height = $data['height'];
        $size->mime_type = $data['mime-type'];

        $urlPath = dirname($this->url);
        $size->url = sprintf('%s/%s', $urlPath, $size->filename);

        return $size;
    }

    /**
     * @param Post $attachment
     *
     * @return array
     */
    protected function fetchMetadataValue(Post $attachment)
    {
        $metaData = $attachment->meta->where('meta_key', '_wp_attachment_metadata')->first();
        if (!$metaData) {
            return [];
        }

        return unserialize($metaData->meta_value);
    }

    /**
     * @param Collection $attachments
     *
     * @return Collection|array
     */
    protected function fetchMultipleMetadataValues(Collection $attachments)
    {
        $metadataValues = [];

        foreach($attachments as $attachment){
            $metadataValues[$attachment->ID] = unserialize(
                $attachment->meta->where('meta_key', '_wp_attachment_metadata')->first()->meta_value
            );
        }

        return $metadataValues;
    }

    /**
     * @param array $imageData
     */
    protected function fillMetadataFields(array $imageData)
    {
        $this->filename = array_key_exists('file', $imageData) ? basename($imageData['file']) : '';
        $this->width = array_key_exists('width', $imageData) ? $imageData['width'] : '';
        $this->height = array_key_exists('height', $imageData) ? $imageData['height'] : '';
        $this->sizes = array_key_exists('sizes', $imageData) ? $imageData['sizes'] : [];
    }
}
