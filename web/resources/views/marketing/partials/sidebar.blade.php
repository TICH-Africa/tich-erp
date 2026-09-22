<aside class="tich-admin-sidebar" id="marketing-admin-sidebar">
    @include('partials.navigation.sidebar-user')
    <p class="tich-admin-sidebar__title">Marketing</p>
    <nav class="tich-admin-sidebar__nav" aria-label="Marketing module navigation">
        <p class="tich-admin-sidebar__title tich-mt-4">Website Content</p>
        @include('partials.navigation.sidebar-link', ['href' => route('marketing.about.index'), 'label' => 'About Us', 'icon' => 'book-open', 'active' => request()->routeIs('marketing.about.*')])
        @include('partials.navigation.sidebar-link', ['href' => route('marketing.blogs.index'), 'label' => 'Blogs', 'icon' => 'layers', 'active' => request()->routeIs('marketing.blogs.*')])
        @include('partials.navigation.sidebar-link', ['href' => route('marketing.pages.index'), 'label' => 'Legal pages', 'icon' => 'file-text', 'active' => request()->routeIs('marketing.pages.*')])
        @include('partials.navigation.sidebar-link', ['href' => route('marketing.events.index'), 'label' => 'Events', 'icon' => 'calendar', 'active' => request()->routeIs('marketing.events.*')])

        <p class="tich-admin-sidebar__title tich-mt-4">Site Settings</p>
        @include('partials.navigation.sidebar-link', ['href' => route('marketing.site-settings.index', ['panel' => 'general']), 'label' => 'Identity & logo', 'icon' => 'settings', 'active' => request()->routeIs('marketing.site-settings.*')])
        @include('partials.navigation.sidebar-link', ['href' => route('marketing.site-settings.index', ['panel' => 'hero']), 'label' => 'Hero slides', 'icon' => 'image', 'active' => request()->routeIs('marketing.site-settings.*')])
        @include('partials.navigation.sidebar-link', ['href' => route('marketing.site-settings.index', ['panel' => 'contact']), 'label' => 'Contact details', 'icon' => 'phone', 'active' => request()->routeIs('marketing.site-settings.*')])
        @include('partials.navigation.sidebar-link', ['href' => route('marketing.site-settings.index', ['panel' => 'social']), 'label' => 'Social links', 'icon' => 'globe', 'active' => request()->routeIs('marketing.site-settings.*')])

        <p class="tich-admin-sidebar__title tich-mt-4">Marketing Tools</p>
        @include('partials.navigation.sidebar-link', ['href' => route('marketing.leads.index'), 'label' => 'Leads', 'icon' => 'users', 'active' => request()->routeIs('marketing.leads.*')])
        @include('partials.navigation.sidebar-link', ['href' => route('marketing.lead-activities.index'), 'label' => 'Lead Activities', 'icon' => 'calendar', 'active' => request()->routeIs('marketing.lead-activities.*')])
        @include('partials.navigation.sidebar-link', ['href' => route('marketing.enrolled-students.index'), 'label' => 'Enrolled Students', 'icon' => 'graduation-cap', 'active' => request()->routeIs('marketing.enrolled-students.*')])
        @include('partials.navigation.sidebar-link', ['href' => route('marketing.reports.index'), 'label' => 'Reports', 'icon' => 'bar-chart', 'active' => request()->routeIs('marketing.reports.*')])
        @include('partials.navigation.sidebar-link', ['href' => route('marketing.analytics.index'), 'label' => 'Analytics', 'icon' => 'bar-chart', 'active' => request()->routeIs('marketing.analytics.*')])
    </nav>
    <div class="tich-admin-sidebar__footer">
        @include('partials.navigation.sidebar-link', ['href' => route('dashboard'), 'label' => 'Back to dashboard', 'icon' => 'arrow-left', 'muted' => true])
    </div>
</aside>
