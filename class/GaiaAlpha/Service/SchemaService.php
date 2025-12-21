<?php

namespace GaiaAlpha\Service;

use GaiaAlpha\Request;

class SchemaService
{
    public static function generateJsonLd($page, $globalSettings)
    {
        $type = $page['schema_type'] ?? 'WebPage';
        $siteTitle = $globalSettings['site_title'] ?? 'Gaia Alpha';
        $siteUrl = Request::scheme() . '://' . Request::host();
        $pageUrl = $page['canonical_url'] ?? ($siteUrl . '/' . ltrim($page['slug'], '/'));

        $data = [
            '@context' => 'https://schema.org',
            '@type' => $type,
            'name' => $page['title'],
            'description' => $page['meta_description'] ?? ($globalSettings['site_description'] ?? ''),
            'url' => $pageUrl,
        ];

        // Specific handling for different types
        switch ($type) {
            case 'Article':
            case 'BlogPosting':
                $data['headline'] = $page['title'];
                if (!empty($page['image'])) {
                    $data['image'] = str_starts_with($page['image'], 'http') ? $page['image'] : ($siteUrl . $page['image']);
                }
                $data['datePublished'] = $page['created_at'];
                $data['dateModified'] = $page['updated_at'] ?? $page['created_at'];
                $data['author'] = [
                    '@type' => 'Organization',
                    'name' => $siteTitle
                ];
                $data['publisher'] = [
                    '@type' => 'Organization',
                    'name' => $siteTitle,
                    'logo' => [
                        '@type' => 'ImageObject',
                        'url' => $globalSettings['site_logo'] ?? ''
                    ]
                ];
                break;

            case 'Product':
                if (!empty($page['schema_data'])) {
                    $customData = json_decode($page['schema_data'], true);
                    if ($customData) {
                        $data = array_merge($data, $customData);
                    }
                }
                break;

            case 'Organization':
                $data['logo'] = $globalSettings['site_logo'] ?? '';
                $data['contactPoint'] = [
                    '@type' => 'ContactPoint',
                    'telephone' => $globalSettings['contact_phone'] ?? '',
                    'contactType' => 'customer service'
                ];
                break;
        }

        // Allow manual overrides from schema_data if not a Product (which already merged)
        if ($type !== 'Product' && !empty($page['schema_data'])) {
            $customData = json_decode($page['schema_data'], true);
            if ($customData) {
                $data = array_merge($data, $customData);
            }
        }

        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}
