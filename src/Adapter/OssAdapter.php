<?php

namespace ManoCode\FileSystem\Adapter;
use League\Flysystem\PathPrefixer;
use OSS\Core\OssException;

/**
 *
 */
class OssAdapter extends \Iidestiny\Flysystem\Oss\OssAdapter
{

    /**
     * @throws OssException
     */
    public function __construct($accessKeyId, $accessKeySecret, $endpoint, $bucket, bool $isCName = false, string $prefix = '', array $buckets = [], ...$params)
    {
        $this->accessKeyId = $accessKeyId;
        $this->accessKeySecret = $accessKeySecret;
        $this->endpoint = $endpoint;
        $this->bucket = $bucket;
        $this->isCName = $isCName;
        $this->prefixer = new PathPrefixer($prefix, '/');
        $this->buckets = $buckets;
        $this->params = $params;
        $this->initClient();
        $this->checkEndpoint();
    }
}
