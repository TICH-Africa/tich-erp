<?php

namespace App\Services\Administration;

class AdministrationNavigationService
{
    /**
     * @return list<array{label: string, icon: string, open: bool, active: bool, items: list<array<string, mixed>>}>
     */
    public function sidebarGroups(): array
    {
        return [
            [
                'label' => 'Planning & funds',
                'icon' => 'calendar',
                'badgeKey' => 'planning-funds',
                'open' => request()->routeIs(
                    'administration.planning.*',
                    'administration.budget-aggregation.*',
                    'administration.approvals.*',
                    'administration.fund-distribution.*',
                    'administration.workflow.*',
                ),
                'active' => request()->routeIs(
                    'administration.planning.*',
                    'administration.budget-aggregation.*',
                    'administration.approvals.*',
                    'administration.fund-distribution.*',
                    'administration.workflow.*',
                ),
                'items' => [
                    $this->item('Multi-tier planning', 'calendar', route('administration.planning.index'), request()->routeIs('administration.planning.*')),
                    $this->item('Budget aggregation', 'layers', route('administration.budget-aggregation.index'), request()->routeIs('administration.budget-aggregation.*')),
                    $this->item('Approval workflow', 'clipboard-check', route('administration.approvals.index'), request()->routeIs('administration.approvals.*'), 'approvals'),
                    $this->item('Fund distribution', 'wallet', route('administration.fund-distribution.index'), request()->routeIs('administration.fund-distribution.*')),
                    $this->item('Annual plan workflow', 'clipboard', route('administration.workflow.index'), request()->routeIs('administration.workflow.*')),
                ],
            ],
            [
                'label' => 'Admissions ops',
                'icon' => 'user-plus',
                'badgeKey' => 'admissions-ops',
                'open' => request()->routeIs(
                    'administration.applications.*',
                    'administration.lifecycle.*',
                    'administration.admission-packages.*',
                ),
                'active' => request()->routeIs(
                    'administration.applications.*',
                    'administration.lifecycle.*',
                    'administration.admission-packages.*',
                ),
                'items' => [
                    $this->item('Application framework', 'file-text', route('administration.applications.index'), request()->routeIs('administration.applications.*'), 'applications'),
                    $this->item('Automated lifecycle', 'refresh-cw', route('administration.lifecycle.index'), request()->routeIs('administration.lifecycle.*'), 'lifecycle'),
                    $this->item('Admission packages', 'mail', route('administration.admission-packages.index'), request()->routeIs('administration.admission-packages.*')),
                ],
            ],
            [
                'label' => 'Compliance',
                'icon' => 'shield-check',
                'badgeKey' => 'compliance',
                'open' => request()->routeIs('administration.statutory.*', 'administration.inspection.*'),
                'active' => request()->routeIs('administration.statutory.*', 'administration.inspection.*'),
                'items' => [
                    $this->item('Statutory tracking', 'file-text', route('administration.statutory.index'), request()->routeIs('administration.statutory.*'), 'statutory'),
                    $this->item('Inspection readiness', 'clipboard-check', route('administration.inspection.index'), request()->routeIs('administration.inspection.*'), 'inspection'),
                ],
            ],
            [
                'label' => 'Procurement & ledger',
                'icon' => 'briefcase',
                'open' => request()->routeIs('administration.procurement-pay.*', 'administration.ledger-sync.*'),
                'active' => request()->routeIs('administration.procurement-pay.*', 'administration.ledger-sync.*'),
                'items' => [
                    $this->item('Procurement-to-pay', 'briefcase', route('administration.procurement-pay.index'), request()->routeIs('administration.procurement-pay.*')),
                    $this->item('QuickBooks sync', 'link', route('administration.ledger-sync.index'), request()->routeIs('administration.ledger-sync.*')),
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function item(string $label, string $icon, string $href, bool $active, ?string $badgeKey = null): array
    {
        $item = [
            'label' => $label,
            'icon' => $icon,
            'href' => $href,
            'active' => $active,
        ];

        if ($badgeKey) {
            $item['badgeKey'] = $badgeKey;
        }

        return $item;
    }
}
