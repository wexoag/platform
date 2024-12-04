---
title: Deprecate implicit http cache clear from cache:clear command + introduce explicit cache:clear:http command
issue: NEXT-39671
---
# Core
* Deprecated `\Shopware\Core\Framework\Adapter\Cache\ReverseProxy\ReverseProxyCacheClearer` so that http cache is not cleared with the `cache:clear` command 
* Added `cache:clear:http` to explicitly clear the http cache
___
# Upgrade Information
## Deprecated `\Shopware\Core\Framework\Adapter\Cache\ReverseProxy\ReverseProxyCacheClearer`

If you relied on `cache:clear` to clear your http cache, you should use `cache:clear:http` instead. However, unless you enable the `v6.7.0.0` feature flag, http cache will still be cleared on `cache:clear`
___
# Next Major Version Changes
## Removed `\Shopware\Core\Framework\Adapter\Cache\ReverseProxy\ReverseProxyCacheClearer`

Use `cache:clear:http` to clear the http cache.
