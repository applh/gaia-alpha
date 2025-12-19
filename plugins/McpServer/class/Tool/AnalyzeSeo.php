<?php

namespace McpServer\Tool;

use GaiaAlpha\Model\Page;

class AnalyzeSeo extends BaseTool
{
    public function execute(array $arguments): array
    {
        $slug = $arguments['slug'] ?? null;
        if (!$slug) {
            throw new \Exception("Slug is required.");
        }
        $keyword = $arguments['keyword'] ?? null;
        $page = Page::findBySlug($slug);
        if (!$page) {
            throw new \Exception("Page not found: $slug");
        }

        $content = $page['content'] ?? '';
        $title = $page['title'] ?? '';
        $metaDesc = $page['meta_description'] ?? '';

        $report = [
            'page' => $slug,
            'score' => 0,
            'checks' => [],
            'suggestions' => []
        ];

        $score = 0;

        // Title check
        $titleLen = mb_strlen($title);
        if ($titleLen >= 50 && $titleLen <= 60) {
            $score += 10;
            $report['checks'][] = "Title length is ideal ($titleLen chars).";
        } else {
            $report['suggestions'][] = "Title length ($titleLen) should be between 50-60 characters.";
        }

        // Meta Description check
        if (!empty($metaDesc)) {
            $metaLen = mb_strlen($metaDesc);
            if ($metaLen >= 150 && $metaLen <= 160) {
                $score += 20;
                $report['checks'][] = "Meta description length is ideal ($metaLen chars).";
            } else {
                $report['suggestions'][] = "Meta description length ($metaLen) should be between 150-160 characters.";
            }
        } else {
            $report['suggestions'][] = "Meta description is missing.";
        }

        // H1 check
        preg_match_all('/<h1[^>]*>(.*?)<\/h1>/is', $content, $h1s);
        $h1Count = count($h1s[0]);
        if ($h1Count === 1) {
            $score += 20;
            $report['checks'][] = "Exactly one H1 tag found.";
        } elseif ($h1Count === 0) {
            $report['suggestions'][] = "H1 tag is missing.";
        } else {
            $report['suggestions'][] = "Multiple H1 tags found ($h1Count). There should be exactly one.";
        }

        // H2 check
        if (preg_match('/<h2[^>]*>/i', $content)) {
            $score += 10;
            $report['checks'][] = "H2 tags are present.";
        } else {
            $report['suggestions'][] = "Consider adding H2 tags for better structure.";
        }

        // Image Alt check
        preg_match_all('/<img[^>]+>/i', $content, $imgs);
        if (count($imgs[0]) > 0) {
            $missingAlt = 0;
            foreach ($imgs[0] as $img) {
                if (stripos($img, 'alt=') === false || preg_match('/alt=["\']\s*["\']/i', $img)) {
                    $missingAlt++;
                }
            }
            if ($missingAlt === 0) {
                $score += 20;
                $report['checks'][] = "All images have alt tags.";
            } else {
                $report['suggestions'][] = "$missingAlt image(s) are missing descriptive alt tags.";
            }
        }

        // Keyword Density check
        if ($keyword) {
            $strippedContent = strip_tags($content);
            $keywordCount = mb_substr_count(mb_strtolower($strippedContent), mb_strtolower($keyword));
            $wordCount = str_word_count($strippedContent);
            $density = ($wordCount > 0) ? ($keywordCount / $wordCount) * 100 : 0;

            if ($density >= 1 && $density <= 3) {
                $score += 20;
                $report['checks'][] = "Keyword density is ideal (" . round($density, 2) . "%).";
            } elseif ($density > 3) {
                $report['suggestions'][] = "Keyword density is high (" . round($density, 2) . "%). Avoid keyword stuffing.";
            } else {
                $report['suggestions'][] = "Keyword '$keyword' not found or density too low (" . round($density, 2) . "%).";
            }
        }

        $report['score'] = round($score);
        return $this->resultJson($report);
    }
}
