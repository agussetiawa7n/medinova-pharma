<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Allowlist sanitiser for rich text that reaches the storefront.
 *
 * The pages used to render AI/admin HTML with strip_tags($html, $allowed).
 * strip_tags only filters TAGS — every attribute on a surviving tag is kept, so
 * `<img src=x onerror="...">` or `<a href="javascript:...">` passed straight
 * through into a `{!! !!}` echo. This filters tags AND attributes, and rejects
 * any URL scheme other than http/https/mailto/tel or a relative path.
 */
class HtmlSanitizer
{
    /** tag => attributes permitted on it */
    private const ALLOWED = [
        'h1' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'h5' => [], 'h6' => [],
        'p' => [], 'br' => [], 'hr' => [], 'blockquote' => [],
        'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [],
        'ul' => [], 'ol' => [], 'li' => [], 'sup' => [], 'sub' => [],
        'code' => [], 'pre' => [],
        'span'  => ['class', 'style'],
        'div'   => ['class', 'style'],
        'table' => ['class', 'style'],
        'thead' => [], 'tbody' => [], 'tfoot' => [], 'tr' => [],
        'th'    => ['colspan', 'rowspan', 'style'],
        'td'    => ['colspan', 'rowspan', 'style'],
        'a'     => ['href', 'title', 'target', 'rel'],
        'img'   => ['src', 'alt', 'title', 'width', 'height', 'loading'],
    ];

    private const URL_ATTRIBUTES = ['href', 'src'];
    private const SAFE_SCHEMES   = ['http', 'https', 'mailto', 'tel'];

    public static function clean(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $doc = new DOMDocument();

        // Wrap so the fragment keeps its own structure, and declare UTF-8 —
        // without the meta tag DOMDocument treats the bytes as ISO-8859-1 and
        // mangles anything non-ASCII (µg, °C, en dashes all appear in this
        // pharmaceutical copy).
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML(
            '<?xml encoding="UTF-8"><div id="__root__">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $doc->getElementById('__root__');
        if (!$root) {
            return '';
        }

        self::sanitiseChildren($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return trim($out);
    }

    private static function sanitiseChildren(DOMNode $node): void
    {
        // Snapshot first: unwrapping a node mutates the live child list.
        $children = iterator_to_array($node->childNodes);

        foreach ($children as $child) {
            if ($child instanceof DOMElement) {
                self::sanitiseElement($child);
                continue;
            }

            // Text nodes are fine; comments and processing instructions are not
            // rendered content and can hide conditional-comment payloads.
            if (!($child instanceof \DOMText)) {
                $child->parentNode?->removeChild($child);
            }
        }
    }

    private static function sanitiseElement(DOMElement $element): void
    {
        $tag = strtolower($element->nodeName);

        // Recurse before any structural change so descendants are handled once.
        self::sanitiseChildren($element);

        if (!array_key_exists($tag, self::ALLOWED)) {
            // Drop the tag but keep its text — losing a stray <font> should not
            // lose the sentence inside it. <script>/<style> content is markup,
            // not prose, so those are removed whole.
            if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'form'], true)) {
                $element->parentNode?->removeChild($element);
                return;
            }

            self::unwrap($element);
            return;
        }

        $permitted = self::ALLOWED[$tag];

        foreach (iterator_to_array($element->attributes ?? []) as $attribute) {
            $name = strtolower($attribute->nodeName);

            if (!in_array($name, $permitted, true)) {
                $element->removeAttribute($attribute->nodeName);
                continue;
            }

            if (in_array($name, self::URL_ATTRIBUTES, true)
                && !self::isSafeUrl($attribute->nodeValue)) {
                $element->removeAttribute($attribute->nodeName);
                continue;
            }

            if ($name === 'style' && !self::isSafeStyle($attribute->nodeValue)) {
                $element->removeAttribute($attribute->nodeName);
            }
        }

        // Any link that opens a new tab gets the opener guard.
        if ($tag === 'a' && $element->getAttribute('target') !== '') {
            $element->setAttribute('rel', 'noopener noreferrer');
        }
    }

    /** Replace an element with its own children, preserving the text. */
    private static function unwrap(DOMElement $element): void
    {
        $parent = $element->parentNode;
        if (!$parent) {
            return;
        }

        while ($element->firstChild) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }

    private static function isSafeUrl(?string $url): bool
    {
        $url = trim((string) $url);
        if ($url === '') {
            return false;
        }

        // Relative paths and anchors carry no scheme and are safe.
        if (str_starts_with($url, '/') || str_starts_with($url, '#')) {
            return true;
        }

        // data: is only allowed for inline images, never for scripts.
        if (str_starts_with(strtolower($url), 'data:image/')) {
            return true;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return $scheme !== '' && in_array($scheme, self::SAFE_SCHEMES, true);
    }

    private static function isSafeStyle(?string $style): bool
    {
        $value = strtolower((string) $style);

        foreach (['expression(', 'javascript:', 'behavior:', 'url(javascript', '@import', '-moz-binding'] as $bad) {
            if (str_contains($value, $bad)) {
                return false;
            }
        }

        return true;
    }
}
