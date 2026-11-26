<?php

namespace App\View\Composers;

use Illuminate\View\View;

/**
 * Clears common @foreach loop variables that leak from page @section content
 * into module sidebars (child sections can run before the layout renders).
 */
class SidebarVariableSanitizer
{
    /** @var list<string> */
    private const LEAK_KEYS = ['label', 'icon', 'badge', 'badgeKey'];

    public function compose(View $view): void
    {
        foreach (self::LEAK_KEYS as $key) {
            $view->offsetUnset($key);
        }
    }
}