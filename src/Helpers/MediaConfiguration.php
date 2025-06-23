<?php

namespace PixlMint\Media\Helpers;

use PixlMint\CMS\Helpers\PluginConfiguration;

class MediaConfiguration extends PluginConfiguration
{
    public function enabledMediaTypes(): array
    {
        return $this->getConfigValueOrFallback('enabled_media_types', []);
    }

    /**
     * @return array|int
     */
    public function cacheConfiguration(): mixed
    {
        return $this->getConfigValueOrFallback('cache_duration', 0);
    }

    public function cacheConfigurationForMediaType(string $mediaType): int
    {
        $cacheConfig = $this->cacheConfiguration();
        if (is_array($cacheConfig)) {
            if (key_exists($mediaType, $cacheConfig)) {
                return $cacheConfig[$mediaType];
            } else if (key_exists('default', $cacheConfig)) {
                return $cacheConfig['default'];
            } else {
                return 0;
            }
        } else {
            return $cacheConfig;
        }
    }

    protected function getPluginConfigKey(): string
    {
        return 'media';
    }
}
